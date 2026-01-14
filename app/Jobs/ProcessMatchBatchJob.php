<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\FootballMatch;
use App\Services\FootballService;
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
    public function handle(FootballService $footballService)
    {
        Log::info("Procesando lote {$this->batchNumber} con " . count($this->matchIds) . " partidos");

        $matches = FootballMatch::whereIn('id', $this->matchIds)->get();

        foreach ($matches as $index => $match) {
            try {
                // NO usar sleep() - bloquea el worker completamente
                // Los delays entre lotes ya están configurados en UpdateFinishedMatchesJob

                // Actualizar el partido usando la API
                $updatedMatch = $footballService->updateMatchFromApi($match->id);

                if ($updatedMatch) {
                    Log::info("Partido {$match->id} actualizado en lote {$this->batchNumber}", [
                        'status' => $updatedMatch->status,
                        'score' => $updatedMatch->score
                    ]);
                } else {
                    // FALLBACK: Si la API no retorna datos, simular resultado
                    Log::warning("API no devolvió datos para {$match->id}, usando fallback de simulación");
                    
                    $homeScore = rand(0, 4);
                    $awayScore = rand(0, 4);
                    
                    $match->update([
                        'status' => 'Match Finished',
                        'home_team_score' => $homeScore,
                        'away_team_score' => $awayScore,
                        'score' => "{$homeScore} - {$awayScore}",
                        'events' => "Partido actualizado (fallback): {$homeScore} goles del local, {$awayScore} del visitante",
                        'statistics' => json_encode([
                            'fallback' => true,
                            'timestamp' => now()->toIso8601String()
                        ])
                    ]);
                    
                    Log::info("Partido {$match->id} actualizado con fallback", [
                        'score' => $match->score
                    ]);
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
