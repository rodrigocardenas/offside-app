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
    protected $description = 'Recupera resultados de partidos antiguos desde Football-Data.org';

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
        // 3. Tengan external_id
        $matches = FootballMatch::whereIn('status', ['Not Started', 'Scheduled', 'In Play'])
            ->where('date', '<=', now()->subHours(2))
            ->where('date', '>=', now()->subDays($days))
            ->where('external_id', '!=', '')
            ->where('external_id', '!=', null)
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
                // Intentar obtener el resultado de Football-Data.org
                $fixtureId = $match->external_id;
                
                // Si el external_id no es numérico, extraerlo
                if (!is_numeric($fixtureId)) {
                    $fixtureId = $this->footballService->extraerFixtureIdDelExternalId(
                        $match->external_id,
                        $match->date->format('Y-m-d'),
                        $match->league
                    );
                }
                
                if (!$fixtureId) {
                    $failed++;
                    $bar->advance();
                    continue;
                }
                
                // Obtener datos del fixture
                $fixture = $this->footballService->obtenerFixtureDirecto($fixtureId);
                
                if (!$fixture) {
                    $failed++;
                    $bar->advance();
                    continue;
                }
                
                // Actualizar el partido
                $homeScore = $fixture['goals']['home'] ?? null;
                $awayScore = $fixture['goals']['away'] ?? null;
                $status = $fixture['fixture']['status'] ?? 'TIMED';
                
                $statusMap = [
                    'TIMED' => 'Not Started',
                    'LIVE' => 'In Play',
                    'IN_PLAY' => 'In Play',
                    'PAUSED' => 'In Play',
                    'FINISHED' => 'Match Finished',
                    'POSTPONED' => 'Postponed',
                    'CANCELLED' => 'Cancelled',
                    'AWARDED' => 'Match Finished',
                ];
                
                $newStatus = $statusMap[$status] ?? 'Not Started';
                $score = "{$homeScore} - {$awayScore}";
                
                $match->update([
                    'home_team' => $fixture['teams']['home']['name'] ?? $match->home_team,
                    'away_team' => $fixture['teams']['away']['name'] ?? $match->away_team,
                    'home_team_score' => $homeScore,
                    'away_team_score' => $awayScore,
                    'score' => $score,
                    'status' => $newStatus,
                    'external_id' => (string)$fixtureId
                ]);
                
                Log::info("Partido actualizado desde Football-Data.org", [
                    'match_id' => $match->id,
                    'teams' => "{$match->home_team} vs {$match->away_team}",
                    'score' => $score,
                    'status' => $newStatus
                ]);
                
                $updated++;
                
            } catch (\Exception $e) {
                Log::error("Error recuperando resultado de partido", [
                    'match_id' => $match->id,
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
