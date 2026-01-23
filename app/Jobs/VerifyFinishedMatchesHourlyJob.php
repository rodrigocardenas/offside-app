<?php

namespace App\Jobs;

use App\Models\FootballMatch;
use App\Services\VerificationMonitoringService;
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
        $this->maxMatches = $maxMatches ?? 30;
        $this->windowHours = $windowHours ?? 72;
        $this->cooldownMinutes = $cooldownMinutes ?? 5;
    }

    public function handle(VerificationMonitoringService $monitoringService): void
    {
        $monitorRun = $monitoringService->start(self::class, null, [
            'max_matches' => $this->maxMatches,
            'window_hours' => $this->windowHours,
        ]);

        try {
            try {
                $matches = $this->findCandidateMatches();
            } catch (Throwable $e) {
                Log::error('VerifyFinishedMatchesHourlyJob - error finding candidates', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }

            if ($matches->isEmpty()) {
                Log::info('VerifyFinishedMatchesHourlyJob - no matches pending verification');
                $monitoringService->finish($monitorRun, ['matches_selected' => 0]);
                return;
            }

            $matchIds = $matches->pluck('id')->all();
            $batchId = Str::uuid()->toString();

            FootballMatch::whereIn('id', $matchIds)->update([
                'last_verification_attempt_at' => now(),
            ]);

            Log::info('VerifyFinishedMatchesHourlyJob - dispatching verification batch', [
                'batch_id' => $batchId,
                'match_count' => count($matchIds),
            ]);

            Bus::batch([
                new BatchGetScoresJob($matchIds, $batchId),
                new BatchExtractEventsJob($matchIds, $batchId),
            ])
                ->catch(function (Batch $batch, Throwable $exception) use ($batchId) {
                    Log::error('VerifyFinishedMatchesHourlyJob - batch encountered error', [
                        'batch_id' => $batchId,
                        'batch_name' => $batch->name,
                        'error' => $exception->getMessage(),
                    ]);
                })
                ->finally(function (Batch $batch) use ($matchIds, $batchId) {
                    // Always attempt to verify questions after score and events are fetched
                    // Some jobs may have partially completed even if batch had errors
                    Log::info('VerifyFinishedMatchesHourlyJob - dispatching question verification', [
                        'batch_id' => $batchId,
                        'match_count' => count($matchIds),
                        'batch_failed_jobs' => $batch->failed(),
                    ]);

                    VerifyAllQuestionsJob::dispatch($matchIds, $batchId);
                })
                ->name('verify-finished-matches-' . $batchId)
                ->dispatch();

            $monitoringService->finish($monitorRun, [
                'matches_selected' => count($matchIds),
                'batch_id' => $batchId,
            ]);
        } catch (Throwable $e) {
            $monitoringService->finish($monitorRun, [
                'matches_selected' => isset($matchIds) ? count($matchIds) : 0,
            ], 'failed', $e->getMessage());
            throw $e;
        }
    }

    protected function findCandidateMatches(): Collection
    {
        $windowStart = now()->subHours($this->windowHours);

        $candidates = FootballMatch::query()
            ->withCount(['questions as pending_questions_count' => function ($query) {
                $query->whereNull('result_verified_at');
            }])
            ->whereIn('status', ['Match Finished', 'FINISHED'])
            ->where('date', '>=', $windowStart)
            ->whereHas('questions', function ($query) {
                $query->whereNull('result_verified_at');
            })
            ->orderByDesc('updated_at')
            ->limit($this->maxMatches * 3)
            ->get();

        $matchesWithPriority = [];

        foreach ($candidates as $match) {
            if (!$this->shouldAttemptVerification($match) || ($match->pending_questions_count ?? 0) === 0) {
                continue;
            }

            $priority = $this->calculatePriority($match);

            if ($match->verification_priority !== $priority) {
                $match->verification_priority = $priority;
                $match->save();
            }

            $matchesWithPriority[] = [
                'match' => $match,
                'priority' => $priority,
            ];
        }

        // Sort by priority (lower number = higher priority)
        usort($matchesWithPriority, function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        // Extract just the matches, limited to maxMatches
        $filtered = collect(array_slice($matchesWithPriority, 0, $this->maxMatches))
            ->pluck('match')
            ->values();

        Log::info('VerifyFinishedMatchesHourlyJob - matches selected for verification', [
            'selected' => $filtered->pluck('id'),
        ]);

        return $filtered;
    }

    protected function shouldAttemptVerification(FootballMatch $match): bool
    {
        if (!$match->last_verification_attempt_at) {
            return true;
        }

        return $match->last_verification_attempt_at->diffInMinutes(now()) >= $this->cooldownMinutes;
    }

    protected function calculatePriority(FootballMatch $match): int
    {
        $minutesSinceUpdate = $match->updated_at ? $match->updated_at->diffInMinutes(now()) : 999;
        $pendingQuestions = $match->pending_questions_count ?? 0;

        if ($minutesSinceUpdate <= 30) {
            return 1;
        }

        if ($pendingQuestions >= 5) {
            return 2;
        }

        return 3;
    }
}
