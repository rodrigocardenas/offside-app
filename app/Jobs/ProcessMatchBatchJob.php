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

                // Actualizar el partido usando la API
                $updatedMatch = $footballService->updateMatchFromApi($match->id);

                if ($updatedMatch) {
                    Log::info("Partido {$match->id} actualizado en lote {$this->batchNumber}", [
                        'status' => $updatedMatch->status,
                        'score' => $updatedMatch->score,
                        'source' => 'API Football'
                    ]);
                } else {
                    // Intentar obtener resultado real de Gemini
                    Log::warning("API no devolvió datos para {$match->id}, intentando con Gemini");
                    
                    $geminiResult = null;
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
                            Log::warning("Error al consultar Gemini para {$match->id}: " . $e->getMessage());
                        }
                    }

                    if ($geminiResult && isset($geminiResult['home_score']) && isset($geminiResult['away_score'])) {
                        // Resultado obtenido de Gemini - usar valores reales
                        $homeScore = $geminiResult['home_score'];
                        $awayScore = $geminiResult['away_score'];
                        $source = "Gemini (web search)";
                        
                        $match->update([
                            'status' => 'Match Finished',
                            'home_team_score' => $homeScore,
                            'away_team_score' => $awayScore,
                            'score' => "{$homeScore} - {$awayScore}",
                            'events' => "Partido actualizado desde {$source}: {$homeScore} goles del local, {$awayScore} del visitante",
                            'statistics' => json_encode([
                                'source' => $source,
                                'verified' => true,
                                'timestamp' => now()->toIso8601String()
                            ])
                        ]);
                        
                        Log::info("Partido {$match->id} actualizado ({$source})", [
                            'score' => "{$homeScore} - {$awayScore}"
                        ]);
                    } else {
                        // NO actualizar si no encontramos resultado verificado
                        Log::warning("No se pudo obtener resultado verificado para {$match->id}", [
                            'home_team' => $match->home_team,
                            'away_team' => $match->away_team,
                            'date' => $match->date,
                            'league' => $match->league,
                            'status_actual' => $match->status
                        ]);
                        
                        // Marcar que se intentó procesar pero no se encontró resultado
                        $match->update([
                            'statistics' => json_encode([
                                'source' => 'NO_ENCONTRADO',
                                'verified' => false,
                                'attempted_at' => now()->toIso8601String(),
                                'api_failed' => true,
                                'gemini_failed' => !$geminiService || $geminiResult === null
                            ])
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error al actualizar partido {$match->id} en lote {$this->batchNumber}", [
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        Log::info("Lote {$this->batchNumber} completado");
    }
}

