<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use App\Services\FootballService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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

                // Obtener eventos y estadísticas desde API Football
                $events = $this->getEventsFromApiFootball($fixtureId);
                $statistics = $this->getStatisticsFromApiFootball($fixtureId);

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
                    'external_id' => (string)$fixtureId,
                    'events' => !empty($events) ? json_encode($events) : $match->events,
                    'statistics' => !empty($statistics) ? json_encode($statistics) : $match->statistics
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

    /**
     * Obtiene eventos desde API Football (api-sports.io) - Plan PRO
     */
    private function getEventsFromApiFootball($fixtureId): array
    {
        try {
            $apiKey = config('services.football.key')
                ?? env('FOOTBALL_API_KEY')
                ?? env('APISPORTS_API_KEY')
                ?? env('API_SPORTS_KEY');

            if (!$apiKey || !$fixtureId || !is_numeric($fixtureId)) {
                return [];
            }

            $response = Http::withoutVerifying()
                ->withHeaders(['x-apisports-key' => $apiKey])
                ->timeout(10)
                ->get("https://v3.football.api-sports.io/fixtures/events", [
                    'fixture' => $fixtureId
                ]);

            if (!$response->successful()) {
                Log::warning("API Football events error: HTTP " . $response->status());
                return [];
            }

            $data = $response->json();
            $events = [];

            if (isset($data['response']) && is_array($data['response'])) {
                Log::info("API Football: Encontrados " . count($data['response']) . " eventos para fixture $fixtureId");

                foreach ($data['response'] as $event) {
                    $eventType = $event['type'] ?? 'unknown';
                    $minute = $event['time']['elapsed'] ?? 'N/A';
                    $team = $event['team']['name'] ?? 'UNKNOWN';
                    $player = $event['player']['name'] ?? 'N/A';

                    // Mapear tipos de eventos
                    $typeMap = [
                        'Goal' => 'GOAL',
                        'Card' => 'YELLOW_CARD',
                        'Subst' => 'SUBSTITUTION'
                    ];

                    $mappedType = $typeMap[$eventType] ?? strtoupper($eventType);
                    $detail = $event['detail'] ?? '';  // ✅ CAPTURAR EL FIELD 'detail'

                    // Si es tarjeta, verificar el color
                    if ($eventType === 'Card') {
                        $card = $detail;
                        if (strpos($card, 'Red Card') !== false) {
                            $mappedType = 'RED_CARD';
                        } else {
                            $mappedType = 'YELLOW_CARD';
                        }
                    }

                    $events[] = [
                        'minute' => (string)$minute,
                        'type' => $mappedType,
                        'team' => $team,
                        'player' => $player,
                        'detail' => $detail  // ✅ AGREGAR 'detail' al evento guardado
                    ];
                }
            }

            return $events;

        } catch (\Exception $e) {
            Log::warning("Error en getEventsFromApiFootball: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene estadísticas desde API Football (api-sports.io) - Plan PRO
     */
    private function getStatisticsFromApiFootball($fixtureId): array
    {
        try {
            $apiKey = config('services.football.key')
                ?? env('FOOTBALL_API_KEY')
                ?? env('APISPORTS_API_KEY')
                ?? env('API_SPORTS_KEY');

            if (!$apiKey || !$fixtureId || !is_numeric($fixtureId)) {
                return [];
            }

            $response = Http::withoutVerifying()
                ->withHeaders(['x-apisports-key' => $apiKey])
                ->timeout(10)
                ->get("https://v3.football.api-sports.io/fixtures/statistics", [
                    'fixture' => $fixtureId
                ]);

            if (!$response->successful()) {
                Log::warning("API Football statistics error: HTTP " . $response->status());
                return [];
            }

            $data = $response->json();
            $statistics = [
                'source' => 'API Football (PRO)',
                'verified' => true,
                'verification_method' => 'api_football',
                'timestamp' => now()->toIso8601String()
            ];

            if (isset($data['response']) && is_array($data['response']) && count($data['response']) >= 2) {
                Log::info("API Football: Encontradas estadísticas para fixture $fixtureId");

                // Estadísticas home (índice 0) y away (índice 1)
                $homeStats = $data['response'][0] ?? [];
                $awayStats = $data['response'][1] ?? [];

                // Procesar posesión
                if (isset($homeStats['statistics'])) {
                    foreach ($homeStats['statistics'] as $stat) {
                        if (($stat['type'] ?? '') === 'Ball Possession') {
                            $statistics['possession_home'] = (int)str_replace('%', '', $stat['value'] ?? 0);
                        }
                    }
                }

                if (isset($awayStats['statistics'])) {
                    foreach ($awayStats['statistics'] as $stat) {
                        if (($stat['type'] ?? '') === 'Ball Possession') {
                            $statistics['possession_away'] = (int)str_replace('%', '', $stat['value'] ?? 0);
                        }
                    }
                }

                // Procesar tarjetas
                $yellowHome = 0;
                $yellowAway = 0;
                $redHome = 0;
                $redAway = 0;

                if (isset($homeStats['statistics'])) {
                    foreach ($homeStats['statistics'] as $stat) {
                        $type = $stat['type'] ?? '';
                        if ($type === 'Yellow Cards') {
                            $yellowHome = (int)$stat['value'];
                        } elseif ($type === 'Red Cards') {
                            $redHome = (int)$stat['value'];
                        }
                    }
                }

                if (isset($awayStats['statistics'])) {
                    foreach ($awayStats['statistics'] as $stat) {
                        $type = $stat['type'] ?? '';
                        if ($type === 'Yellow Cards') {
                            $yellowAway = (int)$stat['value'];
                        } elseif ($type === 'Red Cards') {
                            $redAway = (int)$stat['value'];
                        }
                    }
                }

                $statistics['yellow_cards_home'] = $yellowHome;
                $statistics['yellow_cards_away'] = $yellowAway;
                $statistics['red_cards_home'] = $redHome;
                $statistics['red_cards_away'] = $redAway;
                $statistics['total_yellow_cards'] = $yellowHome + $yellowAway;
                $statistics['total_red_cards'] = $redHome + $redAway;
            }

            return $statistics;

        } catch (\Exception $e) {
            Log::warning("Error en getStatisticsFromApiFootball: " . $e->getMessage());
            return [];
        }
    }
}
