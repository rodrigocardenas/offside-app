<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\FootballMatch;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;

class ExtractMatchDetailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos
    public $tries = 3;

    /**
     * Execute the job.
     *
     * RESPONSABILIDAD: Extraer detalles (eventos, posesión, tarjetas) de partidos finalizados
     * que aún no tengan estos datos en formato JSON.
     *
     * FLUJO:
     * 1. Buscar partidos finalizados SIN eventos JSON válido
     * 2. Para cada uno, llamar getDetailedMatchData() de Gemini
     * 3. Si obtiene eventos → Actualizar con events JSON + statistics
     * 4. Si no obtiene → Dejar como está (score-based questions podrán verificarse igual)
     */
    public function handle(GeminiService $geminiService)
    {
        Log::info('Iniciando extracción de detalles de partidos (ExtractMatchDetailsJob)');

        try {
            // Buscar partidos que necesitan detalles
            $matches = FootballMatch::where('status', 'Match Finished')
                ->whereDate('updated_at', '>=', now()->subHours(12))
                ->limit(50) // Procesar máximo 50 partidos por ejecución
                ->get();

            if ($matches->isEmpty()) {
                Log::info('No hay partidos para enriquecer con detalles');
                return;
            }

            Log::info("Procesando {$matches->count()} partidos para extraer detalles");

            $successCount = 0;
            $failureCount = 0;

            foreach ($matches as $match) {
                try {
                    // Verificar si ya tiene eventos JSON válido
                    $hasValidEvents = $this->hasValidEventsJSON($match);

                    if ($hasValidEvents) {
                        Log::debug("Match {$match->id} ya tiene eventos JSON válido, saltando...");
                        continue;
                    }

                    Log::info("Extrayendo detalles para match {$match->id}: {$match->home_team} vs {$match->away_team}");

                    // Intentar obtener datos detallados
                    $detailedData = $geminiService->getDetailedMatchData(
                        $match->home_team,
                        $match->away_team,
                        $match->date,
                        $match->league,
                        true // Force refresh (porque pasó tiempo desde el primer intento)
                    );

                    if ($detailedData && !empty($detailedData['events'])) {
                        // Excelente, tenemos eventos
                        $eventCount = count($detailedData['events']);

                        $updateData = [
                            'events' => json_encode($detailedData['events']),
                            'statistics' => json_encode([
                                'source' => 'Gemini (web search - VERIFIED)',
                                'verified' => true,
                                'verification_method' => 'grounding_search',
                                'has_detailed_events' => true,
                                'detailed_event_count' => $eventCount,
                                'first_goal_scorer' => $detailedData['first_goal_scorer'] ?? null,
                                'last_goal_scorer' => $detailedData['last_goal_scorer'] ?? null,
                                'total_yellow_cards' => $detailedData['total_yellow_cards'] ?? 0,
                                'total_red_cards' => $detailedData['total_red_cards'] ?? 0,
                                'total_own_goals' => $detailedData['total_own_goals'] ?? 0,
                                'total_penalty_goals' => $detailedData['total_penalty_goals'] ?? 0,
                                'home_possession' => $detailedData['home_possession'] ?? null,
                                'away_possession' => $detailedData['away_possession'] ?? null,
                                'enriched_at' => now()->toIso8601String(),
                                'timestamp' => now()->toIso8601String()
                            ])
                        ];

                        $match->update($updateData);

                        Log::info("✅ Detalles extraídos para match {$match->id}", [
                            'event_count' => $eventCount,
                            'first_goal' => $detailedData['first_goal_scorer'] ?? 'N/A',
                            'yellow_cards' => $detailedData['total_yellow_cards'] ?? 0,
                            'red_cards' => $detailedData['total_red_cards'] ?? 0
                        ]);

                        $successCount++;
                    } elseif ($detailedData) {
                        // Tenemos score pero no eventos (probablemente Gemini no encontró info detallada)
                        Log::warning("Detalles parciales para match {$match->id} - sin eventos", [
                            'score' => "{$detailedData['home_goals']} - {$detailedData['away_goals']}"
                        ]);
                        $failureCount++;
                    } else {
                        // Gemini no devolvió nada
                        Log::warning("No se pudieron obtener detalles para match {$match->id} - Gemini retornó NULL");
                        $failureCount++;
                    }

                } catch (\Exception $e) {
                    Log::error("Error extrayendo detalles para match {$match->id}", [
                        'error' => $e->getMessage()
                    ]);
                    $failureCount++;
                }
            }

            Log::info("Extracción de detalles completada", [
                'total_processed' => $matches->count(),
                'success' => $successCount,
                'failures' => $failureCount,
                'success_rate' => $matches->count() > 0 ? round(($successCount / $matches->count()) * 100, 1) . '%' : 'N/A'
            ]);

        } catch (\Exception $e) {
            Log::error('Error crítico en ExtractMatchDetailsJob', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Verificar si un match ya tiene eventos JSON válido
     */
    private function hasValidEventsJSON(FootballMatch $match): bool
    {
        if (!$match->events) {
            return false;
        }

        // Si es string, intentar parsear como JSON
        if (is_string($match->events)) {
            $parsed = json_decode($match->events, true);

            // Si es un array con objetos (eventos), es válido
            if (is_array($parsed) && count($parsed) > 0) {
                // Verificar que tiene estructura de evento
                $first = $parsed[0];
                if (is_array($first) && isset($first['type']) && isset($first['team'])) {
                    return true; // JSON válido con eventos
                }
            }

            // Si es solo texto descriptivo, no es eventos JSON válido
            return false;
        }

        // Si es un array ya (algo raro pero posible), considerarlo válido
        return is_array($match->events) && count($match->events) > 0;
    }
}
