<?php

namespace App\Jobs;

use App\Jobs\Concerns\InteractsWithMatchStatistics;
use App\Models\FootballMatch;
use App\Services\FootballService;
use App\Services\GeminiBatchService;
use App\Services\VerificationMonitoringService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class BatchGetScoresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable, InteractsWithMatchStatistics;

    public $timeout = 600;
    public $tries = 1;

    /** @var array<int> */
    protected array $matchIds;
    protected string $batchId;
    protected bool $forceRefresh;

    public function __construct(array $matchIds, string $batchId, bool $forceRefresh = false)
    {
        $this->matchIds = $matchIds;
        $this->batchId = $batchId;
        $this->forceRefresh = $forceRefresh;
    }

    public function handle(
        FootballService $footballService,
        GeminiBatchService $geminiBatchService,
        VerificationMonitoringService $monitoringService
    ): void
    {
        $monitorRun = $monitoringService->start(self::class, $this->batchId, [
            'match_ids' => $this->matchIds,
        ]);

        $matchesCount = 0;
        $pendingForGemini = [];
        $alreadyComplete = 0;
        $apiUpdated = 0;
        $updatedCount = 0;

        try {
            $matches = FootballMatch::whereIn('id', $this->matchIds)
                ->with('competition')
                ->get();

            $matchesCount = $matches->count();

            if ($matches->isEmpty()) {
                Log::info('BatchGetScoresJob - no matches found for provided IDs', ['batch_id' => $this->batchId]);
                $monitoringService->finish($monitorRun, ['matches_total' => 0]);
                return;
            }

            foreach ($matches as $match) {
                if ($this->hasValidScore($match)) {
                    $alreadyComplete++;
                    continue;
                }

                try {
                    $updatedMatch = $footballService->updateMatchFromApi($match->id);
                } catch (Throwable $e) {
                    Log::warning('BatchGetScoresJob - football API update failed', [
                        'batch_id' => $this->batchId,
                        'match_id' => $match->id,
                        'error' => $e->getMessage(),
                    ]);
                    $updatedMatch = null;
                }

                if ($updatedMatch && $this->hasValidScore($updatedMatch)) {
                    $apiUpdated++;
                    continue;
                }

                $pendingForGemini[] = $match;
            }

            if (empty($pendingForGemini)) {
                Log::info('BatchGetScoresJob - all matches resolved via football API', ['batch_id' => $this->batchId]);
                $monitoringService->finish($monitorRun, [
                    'matches_total' => $matchesCount,
                    'already_scored' => $alreadyComplete,
                    'api_updates' => $apiUpdated,
                    'gemini_candidates' => 0,
                    'gemini_updates' => 0,
                ]);
                return;
            }

            $results = $geminiBatchService->getMultipleMatchResults($pendingForGemini, $this->forceRefresh);

            if (empty($results)) {
                Log::warning('BatchGetScoresJob - Gemini batch returned no results', ['batch_id' => $this->batchId]);
                $monitoringService->finish($monitorRun, [
                    'matches_total' => $matchesCount,
                    'already_scored' => $alreadyComplete,
                    'api_updates' => $apiUpdated,
                    'gemini_candidates' => count($pendingForGemini),
                    'gemini_updates' => 0,
                ]);
                return;
            }

            $matchesById = $matches->keyBy('id');

            foreach ($results as $result) {
                $matchId = $result['match_id'] ?? null;
                if (!$matchId || !$matchesById->has($matchId)) {
                    continue;
                }

                $match = $matchesById->get($matchId);

                if (!$this->isFinishedScorePayload($result)) {
                    continue;
                }

                $this->applyScoreUpdate($match, $result);
                $updatedCount++;
            }

            Log::info('BatchGetScoresJob - scores updated via Gemini batch', [
                'batch_id' => $this->batchId,
                'updated_matches' => $updatedCount,
            ]);

            $monitoringService->finish($monitorRun, [
                'matches_total' => $matchesCount,
                'already_scored' => $alreadyComplete,
                'api_updates' => $apiUpdated,
                'gemini_candidates' => count($pendingForGemini),
                'gemini_updates' => $updatedCount,
            ]);
        } catch (Throwable $e) {
            $monitoringService->finish($monitorRun, [
                'matches_total' => $matchesCount,
                'already_scored' => $alreadyComplete,
                'api_updates' => $apiUpdated,
                'gemini_candidates' => count($pendingForGemini),
                'gemini_updates' => $updatedCount,
            ], 'failed', $e->getMessage());
            throw $e;
        }
    }

    protected function hasValidScore(FootballMatch $match): bool
    {
        return $match->home_team_score !== null && $match->away_team_score !== null;
    }

    protected function isFinishedScorePayload(array $result): bool
    {
        if (($result['status'] ?? 'finished') !== 'finished') {
            return false;
        }

        return isset($result['home_goals'], $result['away_goals']);
    }

    protected function applyScoreUpdate(FootballMatch $match, array $result): void
    {
        $home = (int) $result['home_goals'];
        $away = (int) $result['away_goals'];

        $statistics = $this->mergeStatistics($match, [
            'source' => 'Gemini (batch results)',
            'verified' => true,
            'verification_method' => $result['source'] ?? 'gemini_batch',
            'batch_id' => $this->batchId,
            'score_updated_at' => now()->toIso8601String(),
        ]);

        $match->update([
            'status' => 'Match Finished',
            'home_team_score' => $home,
            'away_team_score' => $away,
            'score' => $home . ' - ' . $away,
            'statistics' => json_encode($statistics),
        ]);
    }
}
