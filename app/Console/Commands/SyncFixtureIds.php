<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncFixtureIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-fixture-ids {--from-date=2026-01-20 : Fecha inicial (YYYY-MM-DD)} {--dry-run : Ver cambios sin aplicar}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Sincroniza fixture IDs de API Football para todos los partidos a partir de una fecha';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $fromDate = $this->option('from-date');
        $dryRun = $this->option('dry-run');
        $apiKey = config('services.football.key') 
            ?? env('FOOTBALL_API_KEY')
            ?? env('APISPORTS_API_KEY')
            ?? env('API_SPORTS_KEY');

        if (!$apiKey) {
            $this->error('❌ FOOTBALL_API_KEY no está configurada');
            return Command::FAILURE;
        }

        $this->info("╔════════════════════════════════════════════════════════════╗");
        $this->info("║ Sincronizando Fixture IDs desde API Football               ║");
        $this->info("╚════════════════════════════════════════════════════════════╝\n");

        $this->line("📅 Fecha inicial: $fromDate");
        if ($dryRun) {
            $this->warn("🔍 MODO DRY-RUN: Sin cambios reales\n");
        }

        // Obtener partidos sin fixture ID o con IDs que podrían ser incorrectos
        $matches = FootballMatch::whereDate('date', '>=', $fromDate)
            ->orderBy('date', 'asc')
            ->get();

        $total = $matches->count();
        $this->line("📊 Total de partidos a procesar: $total\n");

        if ($total === 0) {
            $this->warn("⚠️ No hay partidos a partir de $fromDate");
            return Command::SUCCESS;
        }

        $updated = 0;
        $failed = 0;
        $unchanged = 0;

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        foreach ($matches as $match) {
            $progressBar->advance();

            try {
                $fixtureId = $this->findFixtureInApiFootball(
                    $match->home_team,
                    $match->away_team,
                    $match->date->format('Y-m-d'),
                    $apiKey
                );

                if ($fixtureId) {
                    if ($match->external_id !== (string)$fixtureId) {
                        $oldId = $match->external_id;
                        
                        if (!$dryRun) {
                            $match->update(['external_id' => $fixtureId]);
                        }
                        
                        $updated++;
                        
                        if ($dryRun) {
                            $this->line("\n  [DRY-RUN] {$match->home_team} vs {$match->away_team}");
                            $this->line("    $oldId → $fixtureId");
                        }
                    } else {
                        $unchanged++;
                    }
                } else {
                    $failed++;
                    Log::warning("Fixture no encontrado en API Football", [
                        'match_id' => $match->id,
                        'home_team' => $match->home_team,
                        'away_team' => $match->away_team,
                        'date' => $match->date
                    ]);
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error("Error sincronizando fixture", [
                    'match_id' => $match->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $progressBar->finish();
        $this->line("\n");

        // Resumen
        $this->info("\n╔════════════════════════════════════════════════════════════╗");
        $this->info("║ RESULTADOS DE SINCRONIZACIÓN                               ║");
        $this->info("╠════════════════════════════════════════════════════════════╣");
        $this->line("  Total procesados: <fg=green>$total</>");
        
        if ($updated > 0) {
            $this->line("  Actualizados: <fg=green>$updated</>");
        }
        
        $this->line("  Sin cambios: <fg=yellow>$unchanged</>");
        
        if ($failed > 0) {
            $this->line("  Errores/No encontrados: <fg=red>$failed</>");
        }
        
        $this->info("╚════════════════════════════════════════════════════════════╝");

        if ($dryRun) {
            $this->warn("\n⚠️ MODO DRY-RUN: Ejecuta sin --dry-run para aplicar cambios");
        }

        return Command::SUCCESS;
    }

    /**
     * Busca un fixture en API Football por nombres de equipos y fecha
     */
    private function findFixtureInApiFootball($homeTeam, $awayTeam, $date, $apiKey): ?int
    {
        try {
            // Buscar en todas las competiciones y ligas para esa fecha
            $response = Http::withoutVerifying()
                ->withHeaders(['x-apisports-key' => $apiKey])
                ->timeout(10)
                ->get("https://v3.football.api-sports.io/fixtures", [
                    'date' => $date
                ]);

            if (!$response->successful()) {
                Log::debug("API Football error: HTTP " . $response->status());
                return null;
            }

            $data = $response->json();

            if (isset($data['response']) && is_array($data['response'])) {
                foreach ($data['response'] as $fixture) {
                    $home = $fixture['teams']['home']['name'] ?? '';
                    $away = $fixture['teams']['away']['name'] ?? '';

                    // Buscar coincidencia de equipos (incluyendo variaciones)
                    if ($this->teamsMatch($home, $homeTeam) && $this->teamsMatch($away, $awayTeam)) {
                        return $fixture['fixture']['id'] ?? null;
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::warning("Error buscando fixture en API Football: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Compara nombres de equipos (manejo de variaciones y abreviaciones)
     */
    private function teamsMatch($name1, $name2): bool
    {
        $name1 = strtolower(trim($name1));
        $name2 = strtolower(trim($name2));

        // Coincidencia exacta
        if ($name1 === $name2) {
            return true;
        }

        // Remover caracteres especiales y números
        $clean1 = preg_replace('/[^a-z\s]/', '', $name1);
        $clean2 = preg_replace('/[^a-z\s]/', '', $name2);

        if ($clean1 === $clean2) {
            return true;
        }

        // Coincidir por palabras clave
        $parts1 = explode(' ', trim($clean1));
        $parts2 = explode(' ', trim($clean2));

        // Si tienen palabras clave similares
        $keyWords1 = array_filter($parts1, fn($w) => strlen($w) > 3);
        $keyWords2 = array_filter($parts2, fn($w) => strlen($w) > 3);

        $intersection = array_intersect($keyWords1, $keyWords2);
        
        // Si hay al menos una palabra clave coincidente
        if (count($intersection) > 0 && count($keyWords1) > 0 && count($keyWords2) > 0) {
            return true;
        }

        return false;
    }
}
