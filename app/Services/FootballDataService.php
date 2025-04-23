<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FootballDataService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.football_data.api_key');
        $this->baseUrl = 'http://api.football-data.org/v4';
    }

    public function getNextMatchesByCompetition($competitionId)
    {
        $cacheKey = "next_matches_{$competitionId}";

        return Cache::remember($cacheKey, 3600, function () use ($competitionId) {
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiKey
            ])->get("{$this->baseUrl}/competitions/{$competitionId}/matches", [
                'status' => 'SCHEDULED',
                'limit' => 10
            ]);

            if ($response->successful()) {
                return $response->json()['matches'];
            }

            return [];
        });
    }

    public function getImportantMatch($matches)
    {
        // Lógica para determinar el partido más importante
        // Por ahora, simplemente devolvemos el primer partido
        return $matches[0] ?? null;
    }

    public function generatePredictiveQuestion($match)
    {
        if (!$match) {
            return null;
        }

        $homeTeam = $match['homeTeam']['name'];
        $awayTeam = $match['awayTeam']['name'];
        $matchDate = \Carbon\Carbon::parse($match['utcDate']);

        return [
            'title' => "¿Qué equipo anotará el primer gol en el partido {$homeTeam} vs {$awayTeam}?",
            'description' => "Partido a jugarse el {$matchDate->format('d/m/Y H:i')}",
            'type' => 'predictive',
            'options' => [
                ['text' => $homeTeam, 'is_correct' => false],
                ['text' => $awayTeam, 'is_correct' => false],
                ['text' => 'Ningún equipo (0-0)', 'is_correct' => false]
            ],
            'available_until' => $matchDate
        ];
    }
}
