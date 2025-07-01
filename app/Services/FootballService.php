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
            'league' => 15,
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

            // Crear un objeto con los datos actualizados sin modificar el registro original
            $updatedMatch = new FootballMatch();
            $updatedMatch->id = $match->id;
            $updatedMatch->home_team = $fixture['teams']['home']['name'] ?? $match->home_team;
            $updatedMatch->away_team = $fixture['teams']['away']['name'] ?? $match->away_team;
            $updatedMatch->date = $fixture['fixture']['date'] ?? $match->date;
            $updatedMatch->status = $fixture['fixture']['status']['long'] ?? $match->status;
            $updatedMatch->home_team_score = $fixture['goals']['home'] ?? null;
            $updatedMatch->away_team_score = $fixture['goals']['away'] ?? null;
            $updatedMatch->score = ($fixture['goals']['home'] && $fixture['goals']['away'])
                ? $fixture['goals']['home'] . ' - ' . $fixture['goals']['away']
                : null;
            $updatedMatch->events = $eventosString;

            return $updatedMatch;
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
            Log::warning("Competencia no soportada: $competition, usando Champions League como fallback");
            $leagueId = $this->leagueMap['champions-league'];
        }

        Log::info("Buscando fixture para: $homeTeam vs $awayTeam en liga $competition (ID: $leagueId), temporada $season");

        $response = Http::withHeaders([
            'X-RapidAPI-Key' => $this->apiKey,
            'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
        ])->get($this->baseUrl . 'fixtures', [
            'league' => $leagueId,
            'season' => $season,
            'status' => 'FT'
        ]);

        Log::info("API Response Status: " . $response->status());
        Log::info("API Response Body: " . $response->body());

        if (!$response->successful()) {
            Log::error("Error en API: " . $response->body());
            return null;
        }

        $fixtures = $response->json('response') ?? [];
        if (!is_array($fixtures)) {
            Log::warning("No se encontraron fixtures o formato incorrecto");
            $fixtures = [];
        }

        Log::info("Fixtures encontrados: " . count($fixtures));

                foreach ($fixtures as $fixture) {
            $home = strtolower($fixture['teams']['home']['name']);
            $away = strtolower($fixture['teams']['away']['name']);
            $homeTeamLower = strtolower($homeTeam);
            $awayTeamLower = strtolower($awayTeam);

            Log::info("Comparando: '$home' vs '$homeTeamLower' y '$away' vs '$awayTeamLower'");

            // Búsqueda más flexible: verificar ambas combinaciones
            $match1 = str_contains($home, $homeTeamLower) && str_contains($away, $awayTeamLower);
            $match2 = str_contains($home, $awayTeamLower) && str_contains($away, $homeTeamLower);

            if ($match1 || $match2) {
                Log::info("¡Fixture encontrado! ID: " . $fixture['fixture']['id']);
                return $fixture['fixture']['id'];
            }
        }

        Log::warning("No se encontró fixture para: $homeTeam vs $awayTeam");

        // Intentar búsqueda sin filtro de estado
        Log::info("Intentando búsqueda sin filtro de estado...");
        $response2 = Http::withHeaders([
            'X-RapidAPI-Key' => $this->apiKey,
            'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
        ])->get($this->baseUrl . 'fixtures', [
            'league' => $leagueId,
            'season' => $season
        ]);

        if ($response2->successful()) {
            $fixtures2 = $response2->json('response') ?? [];
            Log::info("Fixtures sin filtro de estado: " . count($fixtures2));

            foreach ($fixtures2 as $fixture) {
                $home = strtolower($fixture['teams']['home']['name']);
                $away = strtolower($fixture['teams']['away']['name']);
                $homeTeamLower = strtolower($homeTeam);
                $awayTeamLower = strtolower($awayTeam);

                $match1 = str_contains($home, $homeTeamLower) && str_contains($away, $awayTeamLower);
                $match2 = str_contains($home, $awayTeamLower) && str_contains($away, $homeTeamLower);

                if ($match1 || $match2) {
                    Log::info("¡Fixture encontrado sin filtro de estado! ID: " . $fixture['fixture']['id'] . " Status: " . $fixture['fixture']['status']['long']);
                    return $fixture['fixture']['id'];
                }
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
        $competition = $match->league ?? 'champions-league'; // Usar el campo league
        $season = 2025; // Temporada actual
        $homeTeam = $match->home_team;
        $awayTeam = $match->away_team;

        Log::info("Datos del partido:", [
            'id' => $match->id,
            'competition' => $competition,
            'season' => $season,
            'home_team' => $homeTeam,
            'away_team' => $awayTeam,
            'date' => $match->date,
            'status' => $match->status
        ]);

        if (!$competition || !$season || !$homeTeam || !$awayTeam) {
            Log::error("Datos faltantes para buscar fixture");
            return null;
        }

        // 3. Buscar el fixtureId en la API
        $fixtureId = $this->buscarFixtureId($competition, $season, $homeTeam, $awayTeam);
        Log::info('Fixture ID: ' . $fixtureId);
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

        Log::info('Response: ' . $response->body());

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
