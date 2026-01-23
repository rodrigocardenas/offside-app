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
     * 
     * 🎯 PIPELINE PRIORITARIO:
     * 1️⃣ API Football (scores en vivo - REQUIERE SUSCRIPCIÓN PAGADA)
     * 2️⃣ Gemini + Web Search (grounding - backup cuando API falla)
     * 3️⃣ NO ACTUALIZA si ambas fallan (política verificada-only)
     */
    public function handle(FootballService $footballService)
    {
        Log::info('═══════════════════════════════════════════════════════════');
        Log::info('📊 INICIANDO: UpdateFinishedMatchesJob - Pipeline Automático');
        Log::info('═══════════════════════════════════════════════════════════');

        // Obtener partidos que deberían haber terminado (fecha + 2 horas de margen)
        // En desarrollo, buscar en un rango más amplio (72 horas)
        $hoursBack = env('APP_ENV') === 'production' ? 24 : 72;

        $finishedMatches = FootballMatch::whereNotIn('status', ['FINISHED', 'Match Finished'])
            ->where('date', '<=', now()->subHours(2))
            ->where('date', '>=', now()->subHours($hoursBack))
            ->pluck('id')
            ->toArray();

        Log::info("📈 Partidos para actualizar: " . count($finishedMatches) . " (últimas {$hoursBack} horas)");

        if (empty($finishedMatches)) {
            Log::info('✓ No hay partidos para procesar');
            return;
        }

        // Dividir en lotes de 5 partidos
        $batches = array_chunk($finishedMatches, 5);
        Log::info("📦 Dividido en " . count($batches) . " lotes de máx 5 partidos cada uno");

        foreach ($batches as $batchNumber => $batch) {
            // Despachar cada lote con delay progresivo
            // Los delays están en la cola, NO bloquean el worker
            $delay = now()->addSeconds(($batchNumber + 1) * 10); // 10s, 20s, 30s, etc.

            ProcessMatchBatchJob::dispatch($batch, $batchNumber + 1)
                ->delay($delay);

            Log::info("🚀 Lote " . ($batchNumber + 1) . " despachado (ejecutará en " . ($batchNumber + 1) * 10 . "s)");
        }

        Log::info('═══════════════════════════════════════════════════════════');
        Log::info('✅ TODOS LOS LOTES DESPACHADOS - Procesamiento en cola');
        Log::info('═══════════════════════════════════════════════════════════');
    }
}
