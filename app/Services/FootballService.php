<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\FootballMatch;

class FootballService
{
    protected $apiKey;
    protected $baseUrl;
    protected $leagueMap;

    public function __construct()
    {
        $this->apiKey = config('services.football_data.api_token');
        // Log::info('API key configurada:', ['key' => $this->apiKey]);
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

    public function getMatch($matchId)
    {
        $response = Http::withHeaders([
            'X-RapidAPI-Key' => $this->apiKey,
            'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
        ])->get($this->baseUrl . 'fixtures', [
            'id' => $matchId
        ]);

        if ($response->successful()) {
            $fixture = $response->json('response.0');
            if (!$fixture) {
                return null;
            }

            // Busca el partido en la base de datos
            $match = FootballMatch::find($matchId);
            if (!$match) {
                // Si no existe, puedes crearlo o retornar null
                return null;
            }
            // dd($fixture);

            // Actualiza los campos principales (ajusta según tu modelo)
            $match->update([
                'home_team' => $fixture['teams']['home']['name'] ?? null,
                'away_team' => $fixture['teams']['away']['name'] ?? null,
                'date' => $fixture['fixture']['date'] ?? null,
                'status' => $fixture['fixture']['status']['long'] ?? null,
                'score_home' => $fixture['goals']['home'] ?? null,
                'score_away' => $fixture['goals']['away'] ?? null,
                'score' => $fixture['goals']['home'] . ' - ' . $fixture['goals']['away'],
                // Puedes guardar más campos si lo necesitas
                'events' => isset($fixture['events']) ? json_encode($fixture['events']) : null,
            ]);

            return $match;
        }

        return null;
    }

    /**
     * Busca el fixtureId de un partido terminado por nombres de equipos, liga y temporada
     */
    public function buscarFixtureId($competition, $season, $homeTeam, $awayTeam)
    {
        $leagueId = $this->leagueMap[$competition] ?? null;
        if (!$leagueId) {
            throw new \Exception("Competencia no soportada: $competition");
        }

        $response = Http::withHeaders([
            'X-RapidAPI-Key' => $this->apiKey,
            'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
        ])->get($this->baseUrl . 'fixtures', [
            'league' => $leagueId,
            'season' => $season,
            'status' => 'FT'
        ]);

        $fixtures = $response->json('response') ?? [];
        if (!is_array($fixtures)) {
            $fixtures = [];
        }
        foreach ($fixtures as $fixture) {
            $home = strtolower($fixture['teams']['home']['name']);
            $away = strtolower($fixture['teams']['away']['name']);
            if (str_contains($home, strtolower($homeTeam)) && str_contains($away, strtolower($awayTeam))) {
                // ddd($home. ' - '. $away, $fixture['fixture']['id']);
                return $fixture['fixture']['id'];
            }
        }
        return null;
    }

    /**
     * Obtiene los goles y autores de un fixture específico
     */
    public function obtenerGolesPartido($fixtureId)
    {
        $response = Http::withHeaders([
            'X-RapidAPI-Key' => $this->apiKey,
            'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
        ])->get($this->baseUrl . 'fixtures', [
            'id' => $fixtureId
        ]);

        $fixture = $response->json('response.0');
        $events = $fixture['events'] ?? [];

        return collect($events)
            ->whereIn('type', ['Goal', 'Goal Penalty', 'Own Goal'])
            ->map(function($evento) {
                return [
                    'minuto' => $evento['time']['elapsed'],
                    'jugador' => $evento['player']['name'],
                    'equipo' => $evento['team']['name'],
                    'tipo' => $evento['type'],
                ];
            })->values();
    }

    /**
     * Actualiza la información de un partido local usando la API externa
     */
    public function updateMatchFromApi($localId)
    {
        // 1. Buscar el partido en tu base de datos
        $match = FootballMatch::find($localId);
        if (!$match) {
            return null;
        }

        // 2. Obtener datos necesarios del registro
        $competition = $match->competition_slug ?? 'champions-league'; // Ajusta según tu modelo
        $season = $match->season ?? 2024;
        $homeTeam = $match->home_team;
        $awayTeam = $match->away_team;

        if (!$competition || !$season || !$homeTeam || !$awayTeam) {
            return null;
        }
        // dd($match);

        // 3. Buscar el fixtureId en la API
        $fixtureId = $this->buscarFixtureId($competition, $season, $homeTeam, $awayTeam);
        if (!$fixtureId) {
            return null;
        }

        // 4. Obtener los datos del fixture y actualizar el registro
        $response = Http::withHeaders([
            'X-RapidAPI-Key' => $this->apiKey,
            'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
        ])->get($this->baseUrl . 'fixtures', [
            'id' => $fixtureId
        ]);

        if ($response->successful()) {
            $fixture = $response->json('response.0');
            if (!$fixture) {
                return null;
            }

            // Procesar eventos como string legible
            $eventos = [];
            if (isset($fixture['events']) && is_array($fixture['events'])) {
                foreach ($fixture['events'] as $evento) {
                    if (in_array($evento['type'], ['Goal', 'Goal Penalty', 'Own Goal'])) {
                        $minuto = $evento['time']['elapsed'];
                        $jugador = $evento['player']['name'];
                        $equipo = $evento['team']['name'];
                        $tipo = $evento['type'];
                        $eventos[] = "{$minuto}' - {$jugador} ({$equipo}) [{$tipo}]";
                    }
                }
            }
            $eventosString = implode(' | ', $eventos);

            $match->update([
                'home_team' => $fixture['teams']['home']['name'] ?? null,
                'away_team' => $fixture['teams']['away']['name'] ?? null,
                'date' => $fixture['fixture']['date'] ?? null,
                'status' => $fixture['fixture']['status']['long'] ?? null,
                'score_home' => $fixture['goals']['home'] ?? null,
                'score_away' => $fixture['goals']['away'] ?? null,
                'score' => $fixture['goals']['home'] . ' - ' . $fixture['goals']['away'],
                'events' => $eventosString,
            ]);
            return $match;
        }

        return null;
    }
}
