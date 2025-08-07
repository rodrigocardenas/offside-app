<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRecentlyFinishedMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutos para el coordinador
    public $tries = 3;

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info('Iniciando procesamiento coordinado de partidos finalizados recientemente');

        try {
            // 1. Actualizar partidos finalizados
            Log::info('Despachando job para actualizar partidos finalizados');
            UpdateFinishedMatchesJob::dispatch()->delay(now()->addSeconds(5));

            // 2. Verificar resultados de preguntas (después de actualizar partidos)
            Log::info('Despachando job para verificar resultados de preguntas');

            // VerifyQuestionResultsJob::dispatch()->delay(now()->addMinutes(2));

            // 3. Crear nuevas preguntas predictivas (al final)
            // Log::info('Despachando job para crear nuevas preguntas predictivas');
            // CreatePredictiveQuestionsJob::dispatch()->delay(now()->addMinutes(5));

            Log::info('Jobs despachados correctamente');

        } catch (\Exception $e) {
            Log::error('Error al despachar jobs', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
