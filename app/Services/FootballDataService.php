<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FootballDataService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.football_data.api_token');
        $this->baseUrl = 'https://api-football-v1.p.rapidapi.com/v3';
    }

    protected function getMockMatches()
    {
        $now = Carbon::now();
        return [
            [
                'id' => 1,
                'utcDate' => $now->addDays(1)->format('Y-m-d\TH:i:s\Z'),
                'matchday' => 35,
                'homeTeam' => ['name' => 'Real Madrid'],
                'awayTeam' => ['name' => 'Barcelona'],
                'status' => 'SCHEDULED'
            ],
            [
                'id' => 2,
                'utcDate' => $now->addDays(1)->format('Y-m-d\TH:i:s\Z'),
                'matchday' => 35,
                'homeTeam' => ['name' => 'Atlético Madrid'],
                'awayTeam' => ['name' => 'Sevilla'],
                'status' => 'SCHEDULED'
            ],
            [
                'id' => 3,
                'utcDate' => $now->addDays(2)->format('Y-m-d\TH:i:s\Z'),
                'matchday' => 35,
                'homeTeam' => ['name' => 'Valencia'],
                'awayTeam' => ['name' => 'Athletic Club'],
                'status' => 'SCHEDULED'
            ],
            [
                'id' => 4,
                'utcDate' => $now->addDays(2)->format('Y-m-d\TH:i:s\Z'),
                'matchday' => 35,
                'homeTeam' => ['name' => 'Real Betis'],
                'awayTeam' => ['name' => 'Real Sociedad'],
                'status' => 'SCHEDULED'
            ],
            [
                'id' => 5,
                'utcDate' => $now->addDays(3)->format('Y-m-d\TH:i:s\Z'),
                'matchday' => 35,
                'homeTeam' => ['name' => 'Villarreal'],
                'awayTeam' => ['name' => 'Osasuna'],
                'status' => 'SCHEDULED'
            ]
        ];
    }

    public function getCurrentMatchday($competitionId)
    {
        if (app()->environment('local')) {
            Log::info('Using mock data for getCurrentMatchday');
            return $this->getMockMatches();
        }

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'X-Auth-Token' => $this->apiKey
                ])->get("{$this->baseUrl}/competitions/{$competitionId}/matches", [
                    'status' => 'SCHEDULED'
                ]);

            Log::info('API Response for getCurrentMatchday:', [
                'competition_id' => $competitionId,
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['matches'] ?? [];
            }

            Log::error('Error getting matches:', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $this->getMockMatches();
        } catch (\Exception $e) {
            Log::error('Exception getting matches: ' . $e->getMessage());
            return $this->getMockMatches();
        }
    }

    public function getNextMatchesByCompetition($competitionId)
    {
        $cacheKey = 'matches_competition_' . $competitionId;
        $matches = Cache::get($cacheKey);

        if (!$matches) {
            $response = Http::withHeaders([
                'X-Auth-Token' => $this->apiKey
            ])->get("{$this->baseUrl}/competitions/{$competitionId}/matches", [
                'status' => 'SCHEDULED',
                'limit' => 5
            ]);

            if ($response->successful()) {
                $matches = $response->json()['matches'];
                Cache::put($cacheKey, $matches, now()->addMinutes(5));
            } else {
                Log::error('Error al obtener partidos:', [
                    'status' => $response->status(),
                    'error' => $response->body()
                ]);
                $matches = [];
            }
        }

        return $matches;
    }

    public function getTeamPlayers($teamName)
    {
        $cacheKey = 'team_players_' . $teamName;
        $players = Cache::get($cacheKey);
        // Cache::forget($cacheKey);

        if (!$players) {
            // Buscar el ID del equipo basado en el nombre
            $response = Http::withHeaders([
                    'X-RapidAPI-Key' => '2ea32fefbamsh0dade5dedb8c255p1f80f9jsn59b5e00f47a5',
                    'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
                ])->get("{$this->baseUrl}/teams", [
                    'name' => $teamName
                ]);

            if (isset($response->json()['response'][0]['team']['id'])) {
                $teamId = $response->json()['response'][0]['team']['id'];

                if (!empty($teamId)) {
                    $team = $teamId;
                    // Obtener los jugadores del equipo
                    $playersResponse = Http::withHeaders([
                            'X-RapidAPI-Key' => '2ea32fefbamsh0dade5dedb8c255p1f80f9jsn59b5e00f47a5',
                            'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
                        ])->get("{$this->baseUrl}/players?team={$team}&season=2024");
                    // dd($playersResponse->json(), $team);

                    if ($playersResponse->successful()) {
                        $players = $playersResponse->json()['response'] ?? [];
                        if (empty($players)) {
                            // Si no hay jugadores, usar datos mock
                            $players = [
                                ['name' => 'Jugador 1'],
                                ['name' => 'Jugador 2'],
                                ['name' => 'Jugador 3'],
                                ['name' => 'Jugador 4']
                            ];
                        }
                        Cache::put($cacheKey, $players, now()->addMinutes(5));
                    } else {
                        // Si hay error en la respuesta, usar datos mock
                        $players = [
                            ['name' => 'Jugador 1'],
                            ['name' => 'Jugador 2'],
                            ['name' => 'Jugador 3'],
                            ['name' => 'Jugador 4']
                        ];
                        Cache::put($cacheKey, $players, now()->addMinutes(5));
                    }
                }
            }
        }

        return $players ?? [];
    }

    public function getImportantMatch($matches)
    {
        if (empty($matches)) {
            return null;
        }

        // Ordenar los partidos por importancia
        // Por ahora, simplemente devolvemos el primer partido programado
        $sortedMatches = collect($matches)->sortBy('utcDate');

        Log::info('Important match selected:', [
            'match' => $sortedMatches->first()
        ]);

        return $sortedMatches->first();
    }

    public function generatePredictiveQuestion($match)
    {
        if (!$match) {
            return null;
        }

        $homeTeam = $match['homeTeam']['name'] ?? '';
        $awayTeam = $match['awayTeam']['name'] ?? '';
        $matchDate = Carbon::parse($match['utcDate'] ?? now());

        return [
            'title' => "¿Quién ganará el partido {$homeTeam} vs {$awayTeam}?",
            'description' => "Predice el ganador del encuentro",
            'type' => 'predictive',
            'available_until' => $matchDate,
            'options' => [
                ['text' => $homeTeam, 'is_correct' => false],
                ['text' => 'Empate', 'is_correct' => false],
                ['text' => $awayTeam, 'is_correct' => false],
            ]
        ];
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
