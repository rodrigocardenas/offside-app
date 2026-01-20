<?php

namespace App\Support;

use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TeamResolver
{
    private const REMOVABLE_TOKENS = [
        'fc', 'f.c', 'f.c.',
        'cf', 'c.f', 'c.f.',
        'club', 'club de futbol', 'club de fútbol',
        'football club',
        'ac', 'a.c', 'a.c.',
        'bc', 'b.c', 'b.c.',
        'cd', 'c.d', 'c.d.',
        'sc', 's.c', 's.c.',
        'calcio', 'sad', 's.a.d', 's.a.d.',
    ];

    private const MIN_TOKEN_OVERLAP = 2;
    private const MAX_LEVENSHTEIN_DISTANCE = 3;

    private static ?Collection $teamCache = null;

    public static function generateShortName(?string $name): ?string
    {
        if (!$name) {
            return null;
        }

        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', Str::ascii($name)) ?? '');

        return substr($normalized !== '' ? $normalized : strtoupper(Str::ascii($name)), 0, 3);
    }

    public static function fallbackExternalId(string $name): string
    {
        return 'team_' . md5($name . microtime(true));
    }

    public static function findByComparableName(string $apiName): ?Team
    {
        $normalized = static::normalizedComparableName($apiName);
        $apiTokens = static::tokenize($apiName);

        if ($normalized === '') {
            return null;
        }

        if (static::$teamCache === null) {
            static::$teamCache = Team::select('id', 'name', 'api_name', 'external_id', 'short_name')->get();
        }

        return static::$teamCache->first(function (Team $team) use ($normalized, $apiTokens) {
            $candidates = [
                $team->name,
                $team->api_name,
            ];

            foreach ($candidates as $value) {
                if (!$value) {
                    continue;
                }

                $candidateNormalized = static::normalizedComparableName($value);
                $candidateTokens = static::tokenize($value);

                if ($candidateNormalized === $normalized) {
                    return true;
                }

                if ($candidateNormalized !== '' && $normalized !== '') {
                    if (str_contains($candidateNormalized, $normalized) || str_contains($normalized, $candidateNormalized)) {
                        return true;
                    }

                    if (static::hasTokenOverlap($apiTokens, $candidateTokens)) {
                        return true;
                    }

                    if (static::isLevenshteinSimilar($candidateNormalized, $normalized)) {
                        return true;
                    }
                }
            }

            return false;
        });
    }

    public static function rememberTeam(Team $team): void
    {
        if (static::$teamCache === null) {
            return;
        }

        $index = static::$teamCache->search(fn (Team $cached) => $cached->id === $team->id);

        if ($index === false) {
            static::$teamCache->push($team);
        } else {
            static::$teamCache[$index] = $team;
        }
    }

    public static function resetCache(): void
    {
        static::$teamCache = null;
    }

    public static function normalizedComparableName(?string $name): string
    {
        $simplified = static::simplifyName($name);
        return $simplified === '' ? '' : preg_replace('/\s+/', '', $simplified);
    }

    public static function simplifyName(?string $name): string
    {
        if (!$name) {
            return '';
        }

        $value = Str::lower(Str::ascii($name));
        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value);

        foreach (self::REMOVABLE_TOKENS as $token) {
            $pattern = '/\b' . preg_quote($token, '/') . '\b/';
            $value = preg_replace($pattern, ' ', $value);
        }

        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value ?? '');
    }

    private static function hasTokenOverlap(array $apiTokens, array $candidateTokens): bool
    {
        if (empty($apiTokens) || empty($candidateTokens)) {
            return false;
        }

        $shared = array_intersect($apiTokens, $candidateTokens);

        return count(array_unique($shared)) >= self::MIN_TOKEN_OVERLAP;
    }

    private static function tokenize(?string $name): array
    {
        $simplified = static::simplifyName($name);

        if ($simplified === '') {
            return [];
        }

        return array_values(array_filter(explode(' ', $simplified)));
    }

    private static function isLevenshteinSimilar(string $valueA, string $valueB): bool
    {
        if ($valueA === '' || $valueB === '') {
            return false;
        }

        $distance = levenshtein($valueA, $valueB);

        if ($distance <= self::MAX_LEVENSHTEIN_DISTANCE) {
            return true;
        }

        $maxLength = max(strlen($valueA), strlen($valueB));

        if ($maxLength === 0) {
            return false;
        }

        $similarity = 1 - ($distance / $maxLength);

        return $similarity >= 0.8;
    }
}
