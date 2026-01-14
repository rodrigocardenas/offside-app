<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\FootballMatch;
use App\Services\FootballService;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;

class ProcessMatchBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutos por lote
    public $tries = 3;

    protected $matchIds;
    protected $batchNumber;

    /**
     * Create a new job instance.
     */
    public function __construct(array $matchIds, int $batchNumber)
    {
        $this->matchIds = $matchIds;
        $this->batchNumber = $batchNumber;
    }

    /**
     * Execute the job.
     * 
     * POLÍTICA DE DATOS VERIFICADOS ÚNICAMENTE:
     * ✅ Solo actualiza con resultados de API Football o Gemini (con verificación web)
     * ❌ NUNCA genera/usa datos aleatorios o ficticios
     * ❌ NUNCA usa rand(), fabricación de scores, o fallbacks no verificados
     * 
     * Si ambas fuentes fallan: El partido NO se actualiza (permanece "Not Started")
     */
    public function handle(FootballService $footballService, GeminiService $geminiService = null)
    {
        Log::info("Procesando lote {$this->batchNumber} con " . count($this->matchIds) . " partidos");

        $matches = FootballMatch::whereIn('id', $this->matchIds)->get();

        // Si no se inyecta GeminiService, intentar obtenerlo de la aplicación
        if (!$geminiService) {
            try {
                $geminiService = app(GeminiService::class);
            } catch (\Exception $e) {
                Log::warning("No se pudo inicializar GeminiService: " . $e->getMessage());
                $geminiService = null;
            }
        }

        foreach ($matches as $index => $match) {
            try {
                // NO usar sleep() - bloquea el worker completamente
                // Los delays entre lotes ya están configurados en UpdateFinishedMatchesJob

                // PASO 1: Intentar obtener resultado de API Football
                Log::info("→ Procesando partido {$match->id}: {$match->home_team} vs {$match->away_team}");
                $updatedMatch = $footballService->updateMatchFromApi($match->id);

                if ($updatedMatch) {
                    Log::info("✅ Partido {$match->id} actualizado desde API Football", [
                        'status' => $updatedMatch->status,
                        'score' => $updatedMatch->score,
                        'source' => 'API Football (VERIFIED)'
                    ]);
                    continue; // Pasar al siguiente partido
                }

                // PASO 2: Si API falla, intentar Gemini (con verificación web real)
                Log::info("⚠️  API no devolvió datos para {$match->id}, intentando con Gemini web search");
                
                $geminiResult = null;
                $geminiError = null;
                
                if ($geminiService) {
                    try {
                        $geminiResult = $geminiService->getMatchResult(
                            $match->home_team,
                            $match->away_team,
                            $match->date,
                            $match->league,
                            false // no force refresh
                        );
                    } catch (\Exception $e) {
                        $geminiError = $e->getMessage();
                        Log::warning("❌ Error al consultar Gemini para {$match->id}: {$geminiError}");
                    }
                } else {
                    $geminiError = "GeminiService no disponible";
                    Log::warning("⚠️  GeminiService no inicializado para {$match->id}");
                }

                // PASO 3: Solo actualizar si Gemini devolvió resultado válido
                if ($geminiResult && isset($geminiResult['home_score']) && isset($geminiResult['away_score'])) {
                    $homeScore = (int) $geminiResult['home_score'];
                    $awayScore = (int) $geminiResult['away_score'];
                    
                    // Validar que los scores sean números válidos
                    if ($homeScore >= 0 && $awayScore >= 0 && $homeScore <= 20 && $awayScore <= 20) {
                        $match->update([
                            'status' => 'Match Finished',
                            'home_team_score' => $homeScore,
                            'away_team_score' => $awayScore,
                            'score' => "{$homeScore} - {$awayScore}",
                            'events' => "✅ Resultado verificado desde Gemini (web search): {$homeScore} goles del local, {$awayScore} del visitante",
                            'statistics' => json_encode([
                                'source' => 'Gemini (web search - VERIFIED)',
                                'verified' => true,
                                'verification_method' => 'grounding_search',
                                'timestamp' => now()->toIso8601String()
                            ])
                        ]);
                        
                        Log::info("✅ Partido {$match->id} actualizado desde Gemini (VERIFICADO)", [
                            'score' => "{$homeScore} - {$awayScore}"
                        ]);
                        continue;
                    } else {
                        Log::error("❌ Scores inválidos de Gemini para {$match->id}: {$homeScore}-{$awayScore}");
                    }
                }

                // PASO 4: Si AMBAS FUENTES FALLAN - NO ACTUALIZAR (política verificada-only)
                Log::warning("❌ NO SE PUEDE VERIFICAR RESULTADO para {$match->id}", [
                    'home_team' => $match->home_team,
                    'away_team' => $match->away_team,
                    'date' => $match->date,
                    'league' => $match->league,
                    'status_actual' => $match->status,
                    'api_failed' => true,
                    'gemini_failed' => !$geminiService || $geminiResult === null,
                    'gemini_error' => $geminiError
                ]);
                
                // Registrar el intento de procesamiento (sin actualizar scores)
                $match->update([
                    'statistics' => json_encode([
                        'source' => 'NO_ENCONTRADO',
                        'verified' => false,
                        'attempted_at' => now()->toIso8601String(),
                        'api_failed' => true,
                        'gemini_failed' => !$geminiService || $geminiResult === null,
                        'policy' => 'VERIFIED_ONLY - NO FAKE DATA',
                        'gemini_error' => $geminiError
                    ])
                ]);
                
                Log::info("✓ Partido {$match->id} marcado como no procesable (sin datos verificados)");

            } catch (\Exception $e) {
                Log::error("❌ ERROR CRÍTICO al actualizar partido {$match->id} en lote {$this->batchNumber}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                continue;
            }
        }

        Log::info("Lote {$this->batchNumber} completado ✓");
    }
}

