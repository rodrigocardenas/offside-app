<?php

namespace App\Services;

use App\Models\FootballMatch;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GeminiBatchService
{
    protected GeminiService $geminiService;
    protected int $maxMatchesPerRequest;
    protected int $resultsCacheTtl;
    protected int $errorCacheTtl;
    protected int $maxBatchRetries;
    protected string $promptTemplate;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
        $this->maxMatchesPerRequest = (int) config('gemini.batch.max_matches_per_request', 8);
        $this->resultsCacheTtl = (int) config('gemini.cache.batch_results_ttl', 120);
        $this->errorCacheTtl = (int) config('gemini.cache.batch_error_ttl', 15);
        $this->maxBatchRetries = (int) config('gemini.batch.max_retries', 2);
        $this->promptTemplate = (string) config('gemini.batch.results_prompt_template');
    }

    /**
     * Obtener resultados para múltiples partidos en una sola llamada a Gemini.
     *
     * @param  array|Collection  $matches
     */
    public function getMultipleMatchResults($matches, bool $forceRefresh = false): array
    {
        $normalizedMatches = $this->normalizeMatches($matches);

        if (empty($normalizedMatches)) {
            return [];
        }

        $chunks = array_chunk($normalizedMatches, $this->maxMatchesPerRequest);
        $batchResults = [];

        foreach ($chunks as $chunk) {
            $cacheKey = $this->buildCacheKey($chunk);
            $errorKey = $this->buildErrorCacheKey($cacheKey);

            if (!$forceRefresh && Cache::has($cacheKey)) {
                Log::debug('Batch Gemini results served from cache', ['cache_key' => $cacheKey]);
                $batchResults = array_merge($batchResults, Cache::get($cacheKey));
                continue;
            }

            if (!$forceRefresh && Cache::has($errorKey)) {
                Log::debug('Skipping Gemini batch because a recent attempt failed', ['cache_key' => $cacheKey]);
                continue;
            }

            $results = $this->fetchBatchResults($chunk);

            if (!empty($results)) {
                Cache::put($cacheKey, $results, now()->addMinutes($this->resultsCacheTtl));
                $batchResults = array_merge($batchResults, $results);
            } else {
                Cache::put($errorKey, true, now()->addMinutes($this->errorCacheTtl));
            }
        }

        return $this->fillMissingMatches($normalizedMatches, $batchResults, $forceRefresh);
    }

    protected function fetchBatchResults(array $matches): array
    {
        $attempt = 0;
        $prompt = $this->buildBatchPrompt($matches);

        while ($attempt < max(1, $this->maxBatchRetries)) {
            $attempt++;

            try {
                $response = $this->geminiService->callGemini($prompt, useGrounding: true);
                $parsed = $this->parseBatchResponse($response, $matches);

                if (!empty($parsed)) {
                    Log::info('Gemini batch results parsed successfully', [
                        'attempt' => $attempt,
                        'match_count' => count($matches),
                        'parsed_count' => count($parsed),
                    ]);
                    return $parsed;
                }

                Log::warning('Gemini batch response returned no usable data', [
                    'attempt' => $attempt,
                    'match_count' => count($matches),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Gemini batch attempt failed', [
                    'attempt' => $attempt,
                    'match_count' => count($matches),
                    'error' => $e->getMessage(),
                ]);

                if ($attempt >= $this->maxBatchRetries) {
                    break;
                }
            }
        }

        return [];
    }

    protected function parseBatchResponse($response, array $matches): array
    {
        if (empty($response)) {
            return [];
        }

        $payload = $this->extractPayload($response);

        if (!$payload || !isset($payload['results']) || !is_array($payload['results'])) {
            return [];
        }

        $lookup = $this->buildLookup($matches);
        $parsed = [];

        foreach ($payload['results'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $home = trim((string) ($entry['home_team'] ?? ''));
            $away = trim((string) ($entry['away_team'] ?? ''));

            if ($home === '' || $away === '') {
                continue;
            }

            $matchRef = $this->matchEntryWithLookup($entry, $lookup);
            $signature = $matchRef['signature'] ?? $this->makeSignature($home, $away, $entry['match_date'] ?? null);

            $parsed[] = [
                'match_id' => $matchRef['id'] ?? null,
                'match_signature' => $signature,
                'home_team' => $home,
                'away_team' => $away,
                'home_goals' => isset($entry['home_goals']) ? (int) $entry['home_goals'] : null,
                'away_goals' => isset($entry['away_goals']) ? (int) $entry['away_goals'] : null,
                'status' => strtolower((string) ($entry['status'] ?? 'finished')),
                'league' => $entry['league'] ?? ($matchRef['league'] ?? null),
                'match_date' => $entry['match_date'] ?? ($matchRef['match_date'] ?? null),
                'source' => 'gemini_batch',
                'raw' => $entry,
            ];
        }

        return $parsed;
    }

    protected function extractPayload($response): ?array
    {
        if (is_array($response) && isset($response['results'])) {
            return $response;
        }

        if (is_array($response) && isset($response['content'])) {
            $decoded = json_decode($response['content'], true);
            return is_array($decoded) ? $decoded : null;
        }

        if (is_string($response)) {
            $decoded = json_decode($response, true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    protected function fillMissingMatches(array $allMatches, array $batchResults, bool $forceRefresh): array
    {
        $resultsBySignature = collect($batchResults)->keyBy(fn ($result) => $result['match_signature'] ?? Str::random());
        $finalResults = $batchResults;

        foreach ($allMatches as $match) {
            if ($resultsBySignature->has($match['signature'])) {
                continue;
            }

            try {
                $fallback = $this->geminiService->getMatchResult(
                    $match['home_team'],
                    $match['away_team'],
                    $match['match_date'] ?? $match['date']?->toDateString(),
                    $match['league'] ?? null,
                    $forceRefresh
                );

                if ($fallback && isset($fallback['home_score'], $fallback['away_score'])) {
                    $finalResults[] = [
                        'match_id' => $match['id'],
                        'match_signature' => $match['signature'],
                        'home_team' => $match['home_team'],
                        'away_team' => $match['away_team'],
                        'home_goals' => (int) $fallback['home_score'],
                        'away_goals' => (int) $fallback['away_score'],
                        'status' => 'finished',
                        'league' => $match['league'],
                        'match_date' => $match['match_date'],
                        'source' => 'gemini_single',
                        'raw' => $fallback,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Fallback Gemini request failed for match', [
                    'match_id' => $match['id'],
                    'home_team' => $match['home_team'],
                    'away_team' => $match['away_team'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $finalResults;
    }

    protected function buildBatchPrompt(array $matches): string
    {
        $matchesList = collect($matches)
            ->map(function (array $match, int $index) {
                $date = $match['match_date'] ?? ($match['date']?->format('Y-m-d') ?? 'fecha desconocida');
                $league = $match['league'] ?? 'liga desconocida';
                return ($index + 1) . '. ' . $match['home_team'] . ' vs ' . $match['away_team'] . ' (' . $date . ') - ' . $league;
            })
            ->implode(PHP_EOL);

        if ($this->promptTemplate === '') {
            return "Proporciona resultados en JSON para los siguientes partidos:\n" . $matchesList;
        }

        return str_replace('{matches_list}', $matchesList, $this->promptTemplate);
    }

    protected function normalizeMatches($matches): array
    {
        if ($matches instanceof Collection) {
            $matches = $matches->all();
        } elseif ($matches instanceof Arrayable) {
            $matches = $matches->toArray();
        }

        $normalized = [];

        foreach ($matches as $match) {
            $normalizedMatch = $this->normalizeMatch($match);

            if ($normalizedMatch) {
                $normalized[] = $normalizedMatch;
            }
        }

        return $normalized;
    }

    protected function normalizeMatch($match): ?array
    {
        if ($match instanceof FootballMatch) {
            $homeTeam = $match->home_team ?? optional($match->homeTeam)->name;
            $awayTeam = $match->away_team ?? optional($match->awayTeam)->name;
            $dateValue = $match->date ?? null;
            $date = $dateValue instanceof Carbon
                ? $dateValue
                : ($dateValue ? $this->parseDate($dateValue) : null);
            $league = $match->league ?? optional($match->competition)->name;

            return $this->formatNormalizedMatch([
                'id' => $match->id,
                'home_team' => $homeTeam,
                'away_team' => $awayTeam,
                'date' => $date,
                'league' => $league,
            ]);
        }

        if (is_array($match)) {
            $homeTeam = $match['home_team'] ?? $match['home'] ?? null;
            $awayTeam = $match['away_team'] ?? $match['away'] ?? null;
            $date = $this->parseDate($match['date'] ?? $match['match_date'] ?? null);
            $league = $match['league'] ?? null;

            return $this->formatNormalizedMatch([
                'id' => $match['id'] ?? null,
                'home_team' => $homeTeam,
                'away_team' => $awayTeam,
                'date' => $date,
                'league' => $league,
            ]);
        }

        return null;
    }

    protected function formatNormalizedMatch(array $data): ?array
    {
        $homeTeam = $data['home_team'] ?? null;
        $awayTeam = $data['away_team'] ?? null;

        if (!$homeTeam || !$awayTeam) {
            return null;
        }

        $date = $data['date'];
        $matchDate = $date instanceof Carbon ? $date->copy()->startOfDay() : null;

        $signature = $this->makeSignature($homeTeam, $awayTeam, $matchDate?->toDateString());
        $teamSignature = $this->makeSignature($homeTeam, $awayTeam, null);

        return [
            'id' => $data['id'] ?? null,
            'home_team' => $homeTeam,
            'away_team' => $awayTeam,
            'date' => $matchDate,
            'match_date' => $matchDate?->toDateString(),
            'league' => $data['league'],
            'signature' => $signature,
            'team_signature' => $teamSignature,
        ];
    }

    protected function parseDate($value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            Log::debug('Unable to parse match date', ['value' => $value, 'error' => $e->getMessage()]);
            return null;
        }
    }

    protected function makeSignature(?string $homeTeam, ?string $awayTeam, ?string $date): string
    {
        $homeSlug = Str::slug((string) $homeTeam);
        $awaySlug = Str::slug((string) $awayTeam);

        $dateSlug = 'any';

        if ($date) {
            $carbonDate = $this->parseDate($date);
            if ($carbonDate) {
                $dateSlug = $carbonDate->format('Ymd');
            }
        }

        return strtolower($homeSlug . '|' . $awaySlug . '|' . $dateSlug);
    }

    protected function buildLookup(array $matches): array
    {
        $exact = [];
        $byTeams = [];

        foreach ($matches as $match) {
            $exact[$match['signature']] = $match;
            $byTeams[$match['team_signature']][] = $match;
        }

        return ['exact' => $exact, 'teams' => $byTeams];
    }

    protected function matchEntryWithLookup(array $entry, array $lookup): ?array
    {
        $signature = $this->makeSignature(
            $entry['home_team'] ?? null,
            $entry['away_team'] ?? null,
            $entry['match_date'] ?? null
        );

        if (isset($lookup['exact'][$signature])) {
            return $lookup['exact'][$signature];
        }

        $teamSignature = $this->makeSignature(
            $entry['home_team'] ?? null,
            $entry['away_team'] ?? null,
            null
        );

        if (!isset($lookup['teams'][$teamSignature])) {
            return null;
        }

        $candidates = $lookup['teams'][$teamSignature];

        if (count($candidates) === 1) {
            return $candidates[0];
        }

        $entryDate = $this->parseDate($entry['match_date'] ?? null);

        if (!$entryDate) {
            return $candidates[0];
        }

        usort($candidates, function (array $a, array $b) use ($entryDate) {
            $diffA = $a['date'] ? abs($entryDate->diffInDays($a['date'])) : PHP_INT_MAX;
            $diffB = $b['date'] ? abs($entryDate->diffInDays($b['date'])) : PHP_INT_MAX;
            return $diffA <=> $diffB;
        });

        return $candidates[0];
    }

    public function getMultipleDetailedMatchData($matches, bool $forceRefresh = false): array
    {
        $normalizedMatches = $this->normalizeMatches($matches);

        if (empty($normalizedMatches)) {
            return [];
        }

        $detailedResults = [];

        foreach ($normalizedMatches as $match) {
            if (!$match['home_team'] || !$match['away_team']) {
                continue;
            }

            try {
                $details = $this->geminiService->getDetailedMatchData(
                    $match['home_team'],
                    $match['away_team'],
                    $match['match_date'] ?? null,
                    $match['league'] ?? null,
                    $forceRefresh
                );

                if (!$details) {
                    continue;
                }

                $detailedResults[] = [
                    'match_id' => $match['id'],
                    'match_signature' => $match['signature'],
                    'details' => $details,
                    'source' => 'gemini_detailed',
                ];
            } catch (Throwable $e) {
                Log::warning('Gemini detailed match data failed', [
                    'match_id' => $match['id'],
                    'home_team' => $match['home_team'],
                    'away_team' => $match['away_team'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $detailedResults;
    }

    protected function buildCacheKey(array $matches): string
    {
        $signatureString = collect($matches)
            ->map(fn (array $match) => $match['signature'])
            ->implode('|');

        return 'gemini:batch:results:' . sha1($signatureString);
    }

    protected function buildErrorCacheKey(string $cacheKey): string
    {
        return $cacheKey . ':error';
    }
}
