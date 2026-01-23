<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use App\Services\FootballService;
use Illuminate\Support\Facades\Log;

class RecoverOldResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recover-old-results {--days=30 : Número de días atrás para buscar partidos}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Recupera resultados de partidos antiguos desde API Football PRO';

    protected $footballService;

    public function __construct(FootballService $footballService)
    {
        parent::__construct();
        $this->footballService = $footballService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days');

        $this->info("╔════════════════════════════════════════════════════════════╗");
        $this->info("║ Recuperando resultados de partidos antiguos (últimos $days días)   ║");
        $this->info("╚════════════════════════════════════════════════════════════╝\n");

        // Buscar partidos que:
        // 1. Tengan fecha <= hace 2 horas (debería haber terminado)
        // 2. Status aún sea "Not Started" o "Scheduled"
        // 3. Tengan external_id numérico (de API Football)
        $matches = FootballMatch::whereIn('status', ['Not Started', 'Scheduled', 'In Play', 'Match Finished'])
            ->where('date', '<=', now()->subHours(2))
            ->where('date', '>=', now()->subDays($days))
            ->where('external_id', 'REGEXP', '^[0-9]+$') // Solo IDs numéricos de API Football
            ->orderBy('date', 'desc')
            ->get();

        $this->line("Partidos encontrados para actualizar: " . count($matches) . "\n");

        if (count($matches) === 0) {
            $this->info("✓ No hay partidos pendientes de actualizar");
            return Command::SUCCESS;
        }

        $updated = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar(count($matches));
        $bar->start();

        foreach ($matches as $match) {
            try {
                $fixtureId = $match->external_id;

                // El external_id ya debe ser numérico por el WHERE clause
                if (!is_numeric($fixtureId)) {
                    Log::warning("Saltando partido con external_id no numérico", ['match_id' => $match->id, 'external_id' => $fixtureId]);
                    $failed++;
                    $bar->advance();
                    continue;
                }

                // Obtener datos del fixture desde API Football
                $fixture = $this->footballService->obtenerFixtureDirecto($fixtureId);

                if (!$fixture) {
                    Log::warning("No se pudo obtener fixture", ['match_id' => $match->id, 'fixture_id' => $fixtureId]);
                    $failed++;
                    $bar->advance();
                    continue;
                }

                // Actualizar el partido
                $homeScore = $fixture['goals']['home'] ?? null;
                $awayScore = $fixture['goals']['away'] ?? null;
                $status = $fixture['fixture']['status'] ?? 'TIMED';

                // Mapear estados de API Football
                $statusMap = [
                    'TBD' => 'Not Started',
                    'NS' => 'Not Started',
                    'LIVE' => 'In Play',
                    'ET' => 'In Play',
                    'BT' => 'In Play',
                    'P' => 'Postponed',
                    'INT' => 'In Play',
                    'FT' => 'Match Finished',
                    'AET' => 'Match Finished',
                    'PEN' => 'Match Finished',
                    'CANC' => 'Cancelled',
                    'ABD' => 'Cancelled',
                    'AWD' => 'Match Finished',
                    'WO' => 'Match Finished',
                ];

                $newStatus = $statusMap[$status] ?? 'Not Started';
                $score = $homeScore !== null && $awayScore !== null 
                    ? "{$homeScore} - {$awayScore}" 
                    : null;

                $match->update([
                    'home_team' => $fixture['teams']['home']['name'] ?? $match->home_team,
                    'away_team' => $fixture['teams']['away']['name'] ?? $match->away_team,
                    'home_team_score' => $homeScore,
                    'away_team_score' => $awayScore,
                    'score' => $score,
                    'status' => $newStatus,
                    'external_id' => (string)$fixtureId
                ]);

                Log::info("Partido actualizado desde API Football PRO", [
                    'match_id' => $match->id,
                    'fixture_id' => $fixtureId,
                    'teams' => "{$match->home_team} vs {$match->away_team}",
                    'score' => $score,
                    'status' => $newStatus
                ]);

                $updated++;

            } catch (\Exception $e) {
                Log::error("Error recuperando resultado de partido", [
                    'match_id' => $match->id,
                    'fixture_id' => $match->external_id ?? 'N/A',
                    'error' => $e->getMessage()
                ]);
                $failed++;
            }

            $bar->advance();
            sleep(1); // Delay para no sobrecargar API
        }

        $bar->finish();

        $this->line("\n\n╔════════════════════════════════════════════════════════════╗");
        $this->line("║ RESUMEN                                                    ║");
        $this->line("╠════════════════════════════════════════════════════════════╣");
        $this->line("║ Partidos actualizados: $updated ✅");
        $this->line("║ Partidos fallidos: $failed ❌");
        $this->line("╚════════════════════════════════════════════════════════════╝");

        return Command::SUCCESS;
    }
}
