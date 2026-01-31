<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\BatchExtractEventsJob;
use Illuminate\Support\Facades\DB;

class DebugBatchExtractJob extends Command
{
    protected $signature = 'app:debug-batch-extract';
    protected $description = 'Ejecutar BatchExtractEventsJob con debug';

    public function handle()
    {
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("🧪 DEBUG BatchExtractEventsJob");
        $this->info("═══════════════════════════════════════════════════════════");

        // Obtener matches que ya tienen eventos guardados desde API Football
        $matches = \App\Models\FootballMatch::whereNotNull('events')
            ->where('events', '!=', '')
            ->limit(5)
            ->get();

        if ($matches->isEmpty()) {
            $this->warn("No hay partidos con eventos para procesar");
            return;
        }

        $matchIds = $matches->pluck('id')->toArray();
        $batchId = 'debug-' . uniqid();

        $this->info("Partidos con eventos encontrados: " . count($matchIds));
        $this->line("IDs: " . implode(', ', $matchIds));
        $this->line("Batch ID: {$batchId}\n");

        try {
            // Inyectar manualmente las dependencias
            $geminiBatch = app(\App\Services\GeminiBatchService::class);
            $monitoring = app(\App\Services\VerificationMonitoringService::class);

            $this->info("➡️  Ejecutando BatchExtractEventsJob::handle()...\n");

            $job = new BatchExtractEventsJob($matchIds, $batchId, false);
            $job->handle($geminiBatch, $monitoring);

            $this->info("\n✅ Job ejecutado sin excepciones");
        } catch (\Exception $e) {
            $this->error("\n❌ EXCEPCIÓN capturada:");
            $this->error("Mensaje: " . $e->getMessage());
            $this->error("\nTraza:");
            $this->error($e->getTraceAsString());

            // Mostrar línea específica del error
            $this->line("\n📍 Línea del error: " . $e->getFile() . ":" . $e->getLine());
        }
    }
}
