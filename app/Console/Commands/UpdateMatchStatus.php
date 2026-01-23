<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use App\Services\FootballService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class UpdateMatchStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-match-status {match_id : ID del partido a actualizar}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Actualiza status, resultado, eventos y estadísticas de un partido específico desde Football-Data.org';

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
        $matchId = $this->argument('match_id');
        
        $match = FootballMatch::find($matchId);
        if (!$match) {
            $this->error("❌ Partido no encontrado con ID: {$matchId}");
            return Command::FAILURE;
        }

        $this->info("\n╔════════════════════════════════════════════════════════════╗");
        $this->info("║ Actualizando Partido Específico                            ║");
        $this->info("╚════════════════════════════════════════════════════════════╝\n");

        $this->line("Partido: {$match->home_team} vs {$match->away_team}");
        $this->line("Fecha: {$match->date->format('Y-m-d H:i')}");
        $this->line("Liga: {$match->league}");
        $this->line("External ID: {$match->external_id}\n");

        try {
            // 1. Extraer fixture ID
            $fixtureId = $match->external_id;
            
            if (!is_numeric($fixtureId)) {
                $this->line("Extrayendo Fixture ID del external_id...");
                $fixtureId = $this->footballService->extraerFixtureIdDelExternalId(
                    $match->external_id,
                    $match->date->format('Y-m-d'),
                    $match->league
                );
            }

            if (!$fixtureId) {
                $this->error("❌ No se pudo extraer Fixture ID");
                return Command::FAILURE;
            }

            $this->line("Fixture ID: <fg=green>{$fixtureId}</>");

            // 2. Obtener fixture de Football-Data.org
            $this->line("\nObteniendo datos de Football-Data.org...");
            $fixture = $this->footballService->obtenerFixtureDirecto($fixtureId);

            if (!$fixture) {
                $this->error("❌ No se pudo obtener fixture de Football-Data.org");
                return Command::FAILURE;
            }

            // 3. Procesar datos
            $homeScore = $fixture['goals']['home'] ?? 0;
            $awayScore = $fixture['goals']['away'] ?? 0;
            $score = "{$homeScore} - {$awayScore}";
            
            $fixtureStatus = $fixture['fixture']['status'] ?? 'TIMED';
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
            $matchStatus = $statusMap[$fixtureStatus] ?? 'Not Started';

            // 4. Intentar obtener eventos detallados
            $this->line("Obteniendo eventos...");
            $events = $this->getDetailedEvents($fixtureId);
            $eventsJson = !empty($events) ? json_encode($events) : null;

            // 5. Intentar obtener estadísticas
            $this->line("Obteniendo estadísticas...");
            $statistics = $this->getDetailedStatistics($fixtureId);
            $statisticsJson = !empty($statistics) ? json_encode($statistics) : null;

            // 6. Actualizar partido
            $this->line("\nActualizando base de datos...");
            
            $updateData = [
                'home_team' => $fixture['teams']['home']['name'] ?? $match->home_team,
                'away_team' => $fixture['teams']['away']['name'] ?? $match->away_team,
                'status' => $matchStatus,
                'home_team_score' => $homeScore,
                'away_team_score' => $awayScore,
                'score' => $score,
                'external_id' => (string)$fixtureId
            ];

            if ($eventsJson) {
                $updateData['events'] = $eventsJson;
            }

            if ($statisticsJson) {
                $updateData['statistics'] = $statisticsJson;
            }

            $match->update($updateData);

            // 7. Mostrar resumen
            $this->info("\n╔════════════════════════════════════════════════════════════╗");
            $this->info("║ ✅ ACTUALIZACIÓN COMPLETADA                                 ║");
            $this->info("╠════════════════════════════════════════════════════════════╣");
            
            $this->line("  Resultado: <fg=green>{$score}</>");
            $this->line("  Status: <fg=green>{$matchStatus}</>");
            
            if ($eventsJson) {
                $eventCount = count(json_decode($eventsJson, true) ?? []);
                $this->line("  Eventos: <fg=green>{$eventCount}</>");
            } else {
                $this->line("  Eventos: <fg=yellow>No disponibles en Football-Data.org</>");
            }

            if ($statisticsJson) {
                $statsData = json_decode($statisticsJson, true);
                $this->line("  Estadísticas: <fg=green>✓</>");
                
                // Mostrar detalles de estadísticas si están disponibles
                if (isset($statsData['total_yellow_cards'])) {
                    $this->line("    • Tarjetas amarillas: {$statsData['total_yellow_cards']}");
                }
                if (isset($statsData['total_red_cards'])) {
                    $this->line("    • Tarjetas rojas: {$statsData['total_red_cards']}");
                }
                if (isset($statsData['detailed_event_count'])) {
                    $this->line("    • Eventos registrados: {$statsData['detailed_event_count']}");
                }
                if (isset($statsData['first_goal_scorer'])) {
                    $this->line("    • Primer gol: {$statsData['first_goal_scorer']}");
                }
                if (isset($statsData['attendance'])) {
                    $this->line("    • Asistencia: {$statsData['attendance']}");
                }
                if (isset($statsData['referee'])) {
                    $this->line("    • Árbitro: {$statsData['referee']}");
                }
            } else {
                $this->line("  Estadísticas: <fg=yellow>No disponibles</>");
            }

            $this->info("╚════════════════════════════════════════════════════════════╝");

            Log::info("Partido actualizado manualmente desde Football-Data.org", [
                'match_id' => $match->id,
                'teams' => "{$match->home_team} vs {$match->away_team}",
                'score' => $score,
                'status' => $matchStatus,
                'has_events' => !empty($eventsJson),
                'has_statistics' => !empty($statisticsJson)
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            Log::error("Error actualizando partido manualmente", [
                'match_id' => $matchId,
                'error' => $e->getMessage()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Obtiene eventos detallados desde Football-Data.org
     */
    private function getDetailedEvents($fixtureId): array
    {
        try {
            $apiKey = config('services.football_data.api_key') 
                ?? env('FOOTBALL_DATA_API_KEY')
                ?? env('FOOTBALL_DATA_API_TOKEN');

            if (!$apiKey) {
                return [];
            }

            // Intentar obtener con el endpoint que incluye más detalles
            $response = Http::withoutVerifying()
                ->withHeaders(['X-Auth-Token' => $apiKey])
                ->timeout(10)
                ->get("https://api.football-data.org/v4/matches/{$fixtureId}");

            if (!$response->successful()) {
                return [];
            }

            $matchData = $response->json();
            $events = [];

            // Obtener goles si están disponibles
            if (isset($matchData['goals']) && is_array($matchData['goals'])) {
                foreach ($matchData['goals'] as $goal) {
                    $events[] = [
                        'minute' => (string)($goal['minute'] ?? 'N/A'),
                        'type' => 'GOAL',
                        'team' => $goal['team']['id'] === $matchData['homeTeam']['id'] ? 'HOME' : 'AWAY',
                        'player' => $goal['scorer'] ?? 'N/A'
                    ];
                }
            }

            // Si hay eventos en la respuesta directa
            if (isset($matchData['events']) && is_array($matchData['events']) && count($matchData['events']) > 0) {
                foreach ($matchData['events'] as $event) {
                    $events[] = [
                        'minute' => (string)($event['minute'] ?? 'N/A'),
                        'type' => $event['type'] ?? 'EVENT',
                        'team' => $event['team']['id'] === $matchData['homeTeam']['id'] ? 'HOME' : 'AWAY',
                        'player' => $event['player'] ?? 'N/A'
                    ];
                }
            }

            return $events;

        } catch (\Exception $e) {
            Log::warning("Error obteniendo eventos de Football-Data.org: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene estadísticas detalladas desde Football-Data.org
     */
    private function getDetailedStatistics($fixtureId): array
    {
        try {
            $apiKey = config('services.football_data.api_key') 
                ?? env('FOOTBALL_DATA_API_KEY')
                ?? env('FOOTBALL_DATA_API_TOKEN');

            if (!$apiKey) {
                return [];
            }

            $response = Http::withoutVerifying()
                ->withHeaders(['X-Auth-Token' => $apiKey])
                ->timeout(10)
                ->get("https://api.football-data.org/v4/matches/{$fixtureId}");

            if (!$response->successful()) {
                return [];
            }

            $matchData = $response->json();
            $statistics = [
                'source' => 'Football-Data.org (OFFICIAL)',
                'verified' => true,
                'verification_method' => 'football_data_api',
                'enriched_at' => now()->toIso8601String(),
                'timestamp' => now()->toIso8601String()
            ];

            // Contar goles
            if (isset($matchData['goals']) && is_array($matchData['goals'])) {
                $statistics['has_detailed_events'] = count($matchData['goals']) > 0;
                $statistics['detailed_event_count'] = count($matchData['goals']);

                // Extraer scorers
                $scorers = [];
                foreach ($matchData['goals'] as $goal) {
                    if (isset($goal['scorer'])) {
                        $scorers[] = $goal['scorer'];
                    }
                }
                if (count($scorers) > 0) {
                    $statistics['first_goal_scorer'] = $scorers[0] ?? null;
                    $statistics['last_goal_scorer'] = end($scorers) ?? null;
                }
            }

            // Si hay información de bookings (tarjetas)
            if (isset($matchData['bookings']) && is_array($matchData['bookings'])) {
                $yellowCards = 0;
                $redCards = 0;
                foreach ($matchData['bookings'] as $booking) {
                    if ($booking['card'] === 'YELLOW') {
                        $yellowCards++;
                    } elseif ($booking['card'] === 'RED') {
                        $redCards++;
                    }
                }
                $statistics['total_yellow_cards'] = $yellowCards;
                $statistics['total_red_cards'] = $redCards;
            }

            // Si hay información de penaltis
            if (isset($matchData['penalties']) && is_array($matchData['penalties'])) {
                $statistics['total_penalty_goals'] = count($matchData['penalties']);
            }

            // Intentar obtener head2head para contexto
            if (isset($matchData['head2head']) && is_array($matchData['head2head'])) {
                $statistics['head_to_head_count'] = count($matchData['head2head']);
            }

            // Status adicional
            $statistics['match_status'] = $matchData['status'] ?? 'UNKNOWN';
            $statistics['attendance'] = $matchData['attendance'] ?? null;
            $statistics['referee'] = $matchData['referee'] ?? null;
            $statistics['stage'] = $matchData['stage'] ?? null;

            return $statistics;

        } catch (\Exception $e) {
            Log::warning("Error obteniendo estadísticas de Football-Data.org: " . $e->getMessage());
            return [];
        }
    }
}
