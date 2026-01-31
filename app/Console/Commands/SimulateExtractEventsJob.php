<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\BatchExtractEventsJob;

class SimulateExtractEventsJob extends Command
{
    protected $signature = 'app:simulate-extract-events {matchIds?} {batchId?}';
    protected $description = 'Ejecutar BatchExtractEventsJob directamente';

    public function handle()
    {
        $matchIdsArg = $this->argument('matchIds') ?? '425,426,484';
        $matchIds = array_map('intval', explode(',', $matchIdsArg));
        $batchId = $this->argument('batchId') ?? 'test-batch-' . uniqid();

        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("🧪 EJECUTANDO BatchExtractEventsJob directamente");
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("Match IDs: " . implode(', ', $matchIds));
        $this->info("Batch ID: {$batchId}");

        $this->info("\n➡️  Ejecutando...\n");

        try {
            $job = new BatchExtractEventsJob($matchIds, $batchId, false);
            $job->handle(
                app(\App\Services\GeminiBatchService::class),
                app(\App\Services\VerificationMonitoringService::class)
            );
            $this->info("\n✅ Job completado sin errores");
        } catch (\Exception $e) {
            $this->error("\n❌ Error durante job:");
            $this->error($e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }
}
