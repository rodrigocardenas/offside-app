<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FootballService
{
    protected $apiKey;
    protected $baseUrl;
    protected $leagueMap;

    public function __construct()
    {
        $this->apiKey = config('services.football_data.api_token');
        Log::info('API key configurada:', ['key' => $this->apiKey]);
        $this->baseUrl = 'https://api-football-v1.p.rapidapi.com/v3/';

        // Puedes extender este arreglo con más ligas
        $this->leagueMap = [
            'premier-league' => 39,
            'la-liga' => 140,
            'serie-a' => 135,
            'bundesliga' => 78,
            'ligue-1' => 61,
            'champions-league' => 2,
            'world-club-championship' => 15,
        ];
    }

    public function getNextMatches(string $competition, int $limit = 5)
    {
        $leagueId = $this->leagueMap[$competition] ?? null;

        if (!$leagueId) {
            throw new \Exception("Competencia no soportada: $competition");
        }

        $response = Http::withHeaders([
            'X-RapidAPI-Key' => '2ea32fefbamsh0dade5dedb8c255p1f80f9jsn59b5e00f47a5',
            'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
        ])->get($this->baseUrl . 'fixtures', [
            'league' => $leagueId,
            'season' => 2025,
            'next' => $limit,
        ]);

        if ($response->failed()) {
            throw new \Exception('Error al obtener los partidos: ' . $response->body());
        }

        return collect($response->json('response'))->map(function ($match) {
            return [
                'fecha' => $match['fixture']['date'],
                'local' => $match['teams']['home']['name'],
                'visitante' => $match['teams']['away']['name'],
                'estado' => $match['fixture']['status']['long'],
                'estadio' => $match['fixture']['venue']['name'],
            ];
        });
    }

    public function getMatches($competitionId)
    {
        $cacheKey = "matches_{$competitionId}";
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($competitionId) {
            $response = Http::withHeaders([
                'X-Auth-Token' => config('services.football_data.api_key'),
            ])->get("http://api.football-data.org/v4/competitions/{$competitionId}/matches");

            if ($response->successful()) {
                return collect($response->json()['matches'])->map(function ($match) {
                    return [
                        'id' => $match['id'],
                        'home_team' => $match['homeTeam']['name'],
                        'away_team' => $match['awayTeam']['name'],
                        'date' => $match['utcDate'],
                        'status' => $match['status'],
                        'score' => [
                            'home' => $match['score']['fullTime']['home'] ?? null,
                            'away' => $match['score']['fullTime']['away'] ?? null,
                        ],
                    ];
                });
            }

            return collect();
        });
    }
}
