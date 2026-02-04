<?php

namespace App\Jobs;

use App\Models\FootballMatch;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class VerifyFinishedMatchesHourlyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900;
    public $tries = 1;

    protected int $maxMatches;
    protected int $windowHours;
    protected int $cooldownMinutes;

    public function __construct(?int $maxMatches = null, ?int $windowHours = null, ?int $cooldownMinutes = null)
    {
        $this->maxMatches = $maxMatches ?? 100;
        $this->windowHours = $windowHours ?? 360; // 15 days instead of 72 hours (3 days)
        $this->cooldownMinutes = $cooldownMinutes ?? 5;
    }

    public function handle(): void
    {
        Log::info('VerifyFinishedMatchesHourlyJob started', [
            'max_matches' => $this->maxMatches,
            'window_hours' => $this->windowHours,
        ]);

        try {
            $matches = $this->findCandidateMatches();

            if ($matches->isEmpty()) {
                Log::info('VerifyFinishedMatchesHourlyJob - no matches pending verification');
                return;
            }

            $matchIds = $matches->pluck('id')->all();
            $batchId = Str::uuid()->toString();

            Log::info('VerifyFinishedMatchesHourlyJob - found candidates', [
                'count' => count($matchIds),
                'ids' => $matchIds,
            ]);

            FootballMatch::whereIn('id', $matchIds)->update([
                'last_verification_attempt_at' => now(),
            ]);

            Log::info('VerifyFinishedMatchesHourlyJob - dispatching batch jobs', [
                'batch_id' => $batchId,
                'match_count' => count($matchIds),
            ]);

            Bus::batch([
                new BatchGetScoresJob($matchIds, $batchId),
                new BatchExtractEventsJob($matchIds, $batchId),
            ])
                ->name('verify-finished-matches-' . $batchId)
                ->dispatch();

            // Dispatch verification job after batch (with delay to allow batch to complete)
            dispatch(new VerifyAllQuestionsJob($matchIds, $batchId))->delay(now()->addSeconds(60));

            Log::info('VerifyFinishedMatchesHourlyJob completed successfully');
        } catch (Throwable $e) {
            Log::error('VerifyFinishedMatchesHourlyJob failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function findCandidateMatches(): Collection
    {
        $windowStart = now()->subHours($this->windowHours);
        $cooldownThreshold = now()->subMinutes($this->cooldownMinutes);

        // OPTIMIZACIÓN: Usar query directo sin withCount para evitar N+1 queries
        // Cargar solo IDs y campos necesarios para scoring
        $candidates = FootballMatch::query()
            ->select('id', 'updated_at', 'last_verification_attempt_at', 'verification_priority')
            ->whereIn('status', ['Match Finished', 'FINISHED', 'Finished'])
            ->where('date', '>=', $windowStart)
            ->where(function ($q) {
                $q->whereNull('last_verification_attempt_at')
                  ->orWhere('last_verification_attempt_at', '<', now()->subMinutes($this->cooldownMinutes));
            })
            ->whereHas('questions', function ($query) {
                $query->whereNull('result_verified_at');
            })
            ->orderByDesc('updated_at')
            ->limit($this->maxMatches)
            ->get();

        // OPTIMIZACIÓN: Batch update de verification_priority en una sola query
        $matchIds = $candidates->pluck('id')->all();
        if (!empty($matchIds)) {
            FootballMatch::whereIn('id', $matchIds)->update([
                'verification_priority' => 2, // Default priority
            ]);
        }

        Log::info('VerifyFinishedMatchesHourlyJob - matches selected for verification', [
            'selected' => $matchIds,
            'count' => count($matchIds),
        ]);

        return $candidates;
    }


    protected function calculatePriority(FootballMatch $match): int
    {
        // Ya no se usa - kept for backwards compatibility if needed
        return 2;
    }
}
