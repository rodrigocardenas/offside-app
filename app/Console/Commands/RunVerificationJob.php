<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\VerifyFinishedMatchesHourlyJob;

class RunVerificationJob extends Command
{
    protected $signature = 'app:run-verification-job';
    protected $description = 'Manually dispatch the verification job for testing';

    public function handle(): int
    {
        $this->info("Dispatching VerifyFinishedMatchesHourlyJob...\n");

        try {
            VerifyFinishedMatchesHourlyJob::dispatch();
            $this->info("✓ Job dispatched successfully");
            $this->line("Check logs and queue to monitor progress");
        } catch (\Exception $e) {
            $this->error("✗ Error dispatching job: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
