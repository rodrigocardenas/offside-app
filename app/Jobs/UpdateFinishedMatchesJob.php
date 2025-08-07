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

class UpdateFinishedMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos
    public $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(FootballService $footballService)
    {
        Log::info('Iniciando actualización de partidos finalizados');

        // Obtener partidos que deberían haber terminado (fecha + 2 horas de margen)
        $finishedMatches = FootballMatch::whereNotIn('status', ['FINISHED', 'Match Finished'])
            ->where('date', '<=', now()->subHours(2))
            ->where('date', '>=', now()->subWeeks(4))
            ->where('league', 'liga-colombia')
            ->pluck('id')
            ->toArray();

        Log::info('Partidos que deberían haber terminado encontrados: ' . count($finishedMatches));
        Log::info($finishedMatches);

        if (empty($finishedMatches)) {
            Log::info('No hay partidos para procesar');
            return;
        }

        // Dividir en lotes de 5 partidos
        $batches = array_chunk($finishedMatches, 5);
        Log::info('Dividiendo en ' . count($batches) . ' lotes de máximo 5 partidos cada uno');

        foreach ($batches as $batchNumber => $batch) {
            // Despachar cada lote con un delay progresivo
            $delay = now()->addSeconds(($batchNumber + 1) * 10); // 10 segundos entre lotes

            ProcessMatchBatchJob::dispatch($batch, $batchNumber + 1)
                ->delay($delay);

            Log::info("Lote " . ($batchNumber + 1) . " despachado para ejecutarse en " . $delay->diffForHumans());
        }

        Log::info('Todos los lotes han sido despachados');
    }
}
