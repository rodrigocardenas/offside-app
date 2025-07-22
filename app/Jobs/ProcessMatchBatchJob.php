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
                // Agregar delay entre requests para evitar rate limiting
                if ($index > 0) {
                    $delaySeconds = 2; // 2 segundos entre cada partido
                    sleep($delaySeconds);
                }

                // Actualizar el partido usando la API
                $updatedMatch = $footballService->updateMatchFromApi($match->id);

                if ($updatedMatch) {
                    Log::info("Partido {$match->id} actualizado en lote {$this->batchNumber}", [
                        'status' => $updatedMatch->status,
                        'score' => $updatedMatch->score
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
