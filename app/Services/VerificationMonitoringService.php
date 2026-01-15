<?php

namespace App\Services;

use App\Models\VerificationRun;
use Illuminate\Support\Facades\Log;
use Throwable;

class VerificationMonitoringService
{
    public function start(string $jobName, ?string $batchId = null, array $context = []): ?VerificationRun
    {
        try {
            return VerificationRun::create([
                'job_name' => $jobName,
                'batch_id' => $batchId,
                'status' => 'running',
                'context' => $context,
                'started_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('VerificationMonitoringService::start failed', [
                'job' => $jobName,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function finish(?VerificationRun $run, array $metrics = [], string $status = 'success', ?string $error = null): void
    {
        if (!$run) {
            return;
        }

        try {
            $finishedAt = now();
            $durationMs = null;

            if ($run->started_at) {
                $durationMs = $run->started_at->diffInMilliseconds($finishedAt);
            }

            $run->update([
                'status' => $status,
                'metrics' => $metrics,
                'error_message' => $error,
                'finished_at' => $finishedAt,
                'duration_ms' => $durationMs,
            ]);
        } catch (Throwable $e) {
            Log::warning('VerificationMonitoringService::finish failed', [
                'job' => $run->job_name,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
