<?php

namespace App\Jobs;

use App\Jobs\Concerns\InteractsWithMatchStatistics;
use App\Models\FootballMatch;
use App\Services\GeminiBatchService;
use App\Services\VerificationMonitoringService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BatchExtractEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable, InteractsWithMatchStatistics;

    public $timeout = 900;
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

    public function handle(GeminiBatchService $geminiBatchService, VerificationMonitoringService $monitoringService): void
    {
        $monitorRun = $monitoringService->start(self::class, $this->batchId, [
            'match_ids' => $this->matchIds,
        ]);

        $matchesTotal = 0;
        $needsDetailsCount = 0;
        $updatedCount = 0;

        try {
            $matches = FootballMatch::whereIn('id', $this->matchIds)->get();
            $matchesTotal = $matches->count();

            if ($matches->isEmpty()) {
                Log::info('BatchExtractEventsJob - no matches found for provided IDs', ['batch_id' => $this->batchId]);
                $monitoringService->finish($monitorRun, ['matches_total' => 0]);
                return;
            }

            $needsDetails = $matches->filter(fn (FootballMatch $match) => !$this->hasStructuredEvents($match));
            $needsDetailsCount = $needsDetails->count();

            if ($needsDetails->isEmpty()) {
                Log::info('BatchExtractEventsJob - all matches already contain detailed events', ['batch_id' => $this->batchId]);
                $monitoringService->finish($monitorRun, [
                    'matches_total' => $matchesTotal,
                    'needs_details' => 0,
                    'updated_matches' => 0,
                ]);
                return;
            }

            $details = $geminiBatchService->getMultipleDetailedMatchData($needsDetails, $this->forceRefresh);

            if (empty($details)) {
                Log::warning('BatchExtractEventsJob - Gemini detailed data unavailable', ['batch_id' => $this->batchId]);
                $monitoringService->finish($monitorRun, [
                    'matches_total' => $matchesTotal,
                    'needs_details' => $needsDetailsCount,
                    'updated_matches' => 0,
                ]);
                return;
            }

            $detailsByMatch = collect($details)->keyBy('match_id');

            foreach ($needsDetails as $match) {
                $payload = $detailsByMatch->get($match->id);

                if (!$payload || empty($payload['details']['events'] ?? [])) {
                    continue;
                }

                $events = $payload['details']['events'];
                $details = $payload['details'];

                // ✅ OPTIMIZACIÓN: Guardar también possession_percentage en statistics
                $statistics = $this->mergeStatistics($match, [
                    'source' => 'Gemini (batch detailed)',
                    'verified' => true,
                    'verification_method' => $payload['source'] ?? 'gemini_detailed',
                    'batch_id' => $this->batchId,
                    'has_detailed_events' => true,
                    'detailed_event_count' => count($events),
                    'enriched_at' => now()->toIso8601String(),
                    // ✅ GUARDAR POSESIÓN
                    'possession' => [
                        'home_percentage' => $details['home_possession'] ?? null,
                        'away_percentage' => $details['away_possession'] ?? null,
                    ],
                    'possession_home' => $details['home_possession'] ?? null,
                    'possession_away' => $details['away_possession'] ?? null,
                    // ✅ GUARDAR OTRAS ESTADÍSTICAS ÚTILES
                    'fouls' => [
                        'home' => $details['home_fouls'] ?? null,
                        'away' => $details['away_fouls'] ?? null,
                    ],
                    'cards' => [
                        'yellow_total' => $details['total_yellow_cards'] ?? null,
                        'red_total' => $details['total_red_cards'] ?? null,
                    ],
                ]);

                $match->update([
                    'events' => json_encode($events),
                    'statistics' => json_encode($statistics),
                ]);

                $updatedCount++;
            }

            Log::info('BatchExtractEventsJob - matches enriched with detailed events', [
                'batch_id' => $this->batchId,
                'updated_matches' => $updatedCount,
            ]);

            $monitoringService->finish($monitorRun, [
                'matches_total' => $matchesTotal,
                'needs_details' => $needsDetailsCount,
                'updated_matches' => $updatedCount,
            ]);
        } catch (\Throwable $e) {
            $monitoringService->finish($monitorRun, [
                'matches_total' => $matchesTotal,
                'needs_details' => $needsDetailsCount,
                'updated_matches' => $updatedCount,
            ], 'failed', $e->getMessage());
            throw $e;
        }
    }

    protected function hasStructuredEvents(FootballMatch $match): bool
    {
        if (!$match->events) {
            return false;
        }

        $events = $match->events;

        if (is_string($events)) {
            $decoded = json_decode($events, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $events = $decoded;
            }
        }

        if (!is_array($events) || empty($events)) {
            return false;
        }

        $first = $events[0];
        return is_array($first) && isset($first['type'], $first['team']);
    }
}
