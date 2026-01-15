<?php

namespace App\Jobs\Concerns;

use App\Models\FootballMatch;

trait InteractsWithMatchStatistics
{
    protected function mergeStatistics(FootballMatch $match, array $payload): array
    {
        return array_merge($this->decodeStatistics($match->statistics), $payload);
    }

    protected function decodeStatistics($statistics): array
    {
        if (is_array($statistics)) {
            return $statistics;
        }

        if (is_string($statistics)) {
            $decoded = json_decode($statistics, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
