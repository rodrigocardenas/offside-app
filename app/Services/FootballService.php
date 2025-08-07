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
            // colombia
            'liga-colombia' => 239,
            // Chile campeonato nacional
            'chile-campeonato-nacional' => 265,
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
            'league' => 239,
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
            $estadisticas = [];

            if (isset($fixture['events']) && is_array($fixture['events'])) {
                foreach ($fixture['events'] as $evento) {
                    $minuto = $evento['time']['elapsed'] ?? 'N/A';
                    $jugador = $evento['player']['name'] ?? 'N/A';
                    $equipo = $evento['team']['name'] ?? 'N/A';
                    $tipo = $evento['type'] ?? 'N/A';
                    $detalle = $evento['detail'] ?? '';

                    switch ($tipo) {
                        case 'Goal':
                        case 'Goal Penalty':
                        case 'Own Goal':
                            $eventos[] = "⚽ {$minuto}' - {$jugador} ({$equipo}) [{$tipo}]";
                            break;
                        case 'Card':
                            $color = $detalle === 'Yellow Card' ? '🟨' : '🟥';
                            $eventos[] = "{$color} {$minuto}' - {$jugador} ({$equipo}) [{$detalle}]";
                            break;
                        case 'Subst':
                            $eventos[] = "🔄 {$minuto}' - {$jugador} ({$equipo}) [Sustitución]";
                            break;
                        case 'Var':
                            $eventos[] = "📺 {$minuto}' - {$jugador} ({$equipo}) [VAR - {$detalle}]";
                            break;
                        default:
                            $eventos[] = "📊 {$minuto}' - {$jugador} ({$equipo}) [{$tipo}]";
                            break;
                    }
                }
            }

            // Procesar estadísticas del partido
            if (isset($fixture['statistics']) && is_array($fixture['statistics'])) {
                foreach ($fixture['statistics'] as $stat) {
                    if (isset($stat['team']) && isset($stat['statistics'])) {
                        $equipo = $stat['team']['name'];
                        $stats = $stat['statistics'];

                        $estadisticas[$equipo] = [
                            'posesion' => $this->buscarEstadistica($stats, 'Ball Possession'),
                            'tiros_totales' => $this->buscarEstadistica($stats, 'Total Shots'),
                            'tiros_a_gol' => $this->buscarEstadistica($stats, 'Shots on Goal'),
                            'faltas' => $this->buscarEstadistica($stats, 'Fouls'),
                            'tarjetas_amarillas' => $this->buscarEstadistica($stats, 'Yellow Cards'),
                            'tarjetas_rojas' => $this->buscarEstadistica($stats, 'Red Cards'),
                        ];
                    }
                }
            }

            $eventosString = implode(' | ', $eventos);
            $estadisticasString = $this->formatearEstadisticas($estadisticas);

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
     * Aplica un delay aleatorio para evitar rate limiting
     */
    private function applyRateLimitDelay($minSeconds = 1, $maxSeconds = 3)
    {
        $delay = rand($minSeconds * 1000000, $maxSeconds * 1000000); // Microsegundos
        usleep($delay);
    }

            /**
     * Extrae el fixtureId del external_id almacenado
     * El external_id puede tener diferentes formatos:
     * 1. Número directo (fixture ID): "123456"
     * 2. Formato con equipos y fecha: "Bucaramanga_Once Caldas_2025-07-22T20:00:00+00:00"
     */
    private function extraerFixtureIdDelExternalId($externalId)
    {
        Log::info("Extrayendo fixtureId del external_id: $externalId");

        // Si el external_id es directamente un número (fixture ID), retornarlo
        if (is_numeric($externalId)) {
            Log::info("External_id es un número directo: $externalId");
            return $externalId;
        }

        // Si el external_id tiene el formato con fecha, necesitamos buscar el fixture
        // por los nombres de equipos y fecha
        $parts = explode('_', $externalId);
        if (count($parts) >= 3) {
            $homeTeam = $parts[0];
            $awayTeam = $parts[1];
            $dateString = $parts[2];

            Log::info("Parsing external_id:", [
                'home_team' => $homeTeam,
                'away_team' => $awayTeam,
                'date_string' => $dateString
            ]);

            try {
                // Convertir la fecha a formato legible
                $date = \Carbon\Carbon::parse($dateString);
                $season = $date->year;

                Log::info("Fecha parseada:", [
                    'date' => $date->toISOString(),
                    'season' => $season
                ]);

                // Intentar con diferentes competencias en orden de prioridad
                $competitions = ['liga-colombia', 'champions-league', 'premier-league', 'la-liga'];

                foreach ($competitions as $competition) {
                    Log::info("Intentando buscar fixture en competencia: $competition");
                    $fixtureId = $this->buscarFixtureId($competition, $season, $homeTeam, $awayTeam);
                    if ($fixtureId) {
                        Log::info("Fixture encontrado en $competition: $fixtureId");
                        return $fixtureId;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error al parsear fecha del external_id: " . $e->getMessage());
            }
        } else {
            Log::warning("Formato de external_id no reconocido: $externalId");
        }

        return null;
    }

    /**
     * Obtiene directamente un fixture usando su ID
     * Método más eficiente que buscarFixtureId
     */
    private function obtenerFixtureDirecto($fixtureId)
    {
        Log::info("Obteniendo fixture directo con ID: $fixtureId");

        $maxRetries = 3;
        $delaySeconds = 2;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $this->applyRateLimitDelay(1, 2);

            $response = Http::withHeaders([
                'X-RapidAPI-Key' => $this->apiKey,
                'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
            ])->get($this->baseUrl . 'fixtures', [
                'id' => $fixtureId
            ]);

            if ($response->successful()) {
                $fixture = $response->json('response.0');
                if ($fixture) {
                    Log::info("Fixture obtenido exitosamente");
                    return $fixture;
                }
            }

            if (str_contains($response->body(), 'Too many requests')) {
                Log::warning("Rate limit alcanzado al obtener fixture directo (attempt $attempt). Esperando $delaySeconds segundos...");
                if ($attempt < $maxRetries) {
                    sleep($delaySeconds);
                    $delaySeconds *= 2;
                }
            } else {
                Log::error("Error al obtener fixture directo (attempt $attempt): " . $response->body());
                break;
            }
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

        // Implementar retry con delay para evitar rate limiting
        $maxRetries = 3;
        $delaySeconds = 2;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            // Aplicar delay aleatorio antes de cada request
            $this->applyRateLimitDelay(1, 2);

            $response = Http::withHeaders([
                'X-RapidAPI-Key' => $this->apiKey,
                'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
            ])->get($this->baseUrl . 'fixtures', [
                'league' => $leagueId,
                'season' => $season,
                'status' => 'FT'
            ]);

            Log::info("API Response Status (attempt $attempt): " . $response->status());

            if ($response->successful()) {
                break;
            }

            if (str_contains($response->body(), 'Too many requests')) {
                Log::warning("Rate limit alcanzado en intento $attempt. Esperando $delaySeconds segundos...");
                if ($attempt < $maxRetries) {
                    sleep($delaySeconds);
                    $delaySeconds *= 2; // Exponential backoff
                }
            } else {
                Log::error("Error en API (attempt $attempt): " . $response->body());
                break;
            }
        }

        if (!$response->successful()) {
            Log::error("Error final en API después de $maxRetries intentos: " . $response->body());
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

        $maxRetries2 = 3;
        $delaySeconds2 = 2;

        for ($attempt2 = 1; $attempt2 <= $maxRetries2; $attempt2++) {
            // Aplicar delay aleatorio antes de cada request
            $this->applyRateLimitDelay(1, 2);

            $response2 = Http::withHeaders([
                'X-RapidAPI-Key' => $this->apiKey,
                'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
            ])->get($this->baseUrl . 'fixtures', [
                'league' => $leagueId,
                'season' => $season
            ]);

            if ($response2->successful()) {
                break;
            }

            if (str_contains($response2->body(), 'Too many requests')) {
                Log::warning("Rate limit alcanzado en búsqueda sin filtro (attempt $attempt2). Esperando $delaySeconds2 segundos...");
                if ($attempt2 < $maxRetries2) {
                    sleep($delaySeconds2);
                    $delaySeconds2 *= 2;
                }
            } else {
                Log::error("Error en API sin filtro (attempt $attempt2): " . $response2->body());
                break;
            }
        }

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
     * Obtiene todos los eventos de un partido (goles, tarjetas, posesión, etc.)
     */
    public function obtenerTodosLosEventos($fixtureId)
    {
        $response = Http::withHeaders([
            'X-RapidAPI-Key' => $this->apiKey,
            'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
        ])->get($this->baseUrl . 'fixtures', [
            'id' => $fixtureId
        ]);

        if (!$response->successful()) {
            return null;
        }

        $fixture = $response->json('response.0');
        if (!$fixture) {
            return null;
        }

        $eventos = [];
        $estadisticas = [];

        // Procesar eventos
        if (isset($fixture['events']) && is_array($fixture['events'])) {
            foreach ($fixture['events'] as $evento) {
                $minuto = $evento['time']['elapsed'] ?? 'N/A';
                $jugador = $evento['player']['name'] ?? 'N/A';
                $equipo = $evento['team']['name'] ?? 'N/A';
                $tipo = $evento['type'] ?? 'N/A';
                $detalle = $evento['detail'] ?? '';

                switch ($tipo) {
                    case 'Goal':
                    case 'Goal Penalty':
                    case 'Own Goal':
                        $eventos[] = "⚽ {$minuto}' - {$jugador} ({$equipo}) [{$tipo}]";
                        break;
                    case 'Card':
                        $color = $detalle === 'Yellow Card' ? '🟨' : '🟥';
                        $eventos[] = "{$color} {$minuto}' - {$jugador} ({$equipo}) [{$detalle}]";
                        break;
                    case 'Subst':
                        $eventos[] = "🔄 {$minuto}' - {$jugador} ({$equipo}) [Sustitución]";
                        break;
                    case 'Var':
                        $eventos[] = "📺 {$minuto}' - {$jugador} ({$equipo}) [VAR - {$detalle}]";
                        break;
                    case 'Normal Goal':
                        $eventos[] = "⚽ {$minuto}' - {$jugador} ({$equipo}) [Gol Normal]";
                        break;
                    case 'Penalty':
                        $eventos[] = "⚽ {$minuto}' - {$jugador} ({$equipo}) [Penalti]";
                        break;
                    case 'Missed Penalty':
                        $eventos[] = "❌ {$minuto}' - {$jugador} ({$equipo}) [Penalti Fallado]";
                        break;
                    default:
                        $eventos[] = "📊 {$minuto}' - {$jugador} ({$equipo}) [{$tipo}]";
                        break;
                }
            }
        }

        // Procesar estadísticas del partido
        if (isset($fixture['statistics']) && is_array($fixture['statistics'])) {
            foreach ($fixture['statistics'] as $stat) {
                if (isset($stat['team']) && isset($stat['statistics'])) {
                    $equipo = $stat['team']['name'];
                    $stats = $stat['statistics'];

                    $estadisticas[$equipo] = [
                        'posesion' => $this->buscarEstadistica($stats, 'Ball Possession'),
                        'tiros_totales' => $this->buscarEstadistica($stats, 'Total Shots'),
                        'tiros_a_gol' => $this->buscarEstadistica($stats, 'Shots on Goal'),
                        'tiros_fuera' => $this->buscarEstadistica($stats, 'Shots off Goal'),
                        'tiros_bloqueados' => $this->buscarEstadistica($stats, 'Blocked Shots'),
                        'tiros_esquina' => $this->buscarEstadistica($stats, 'Corner Kicks'),
                        'faltas' => $this->buscarEstadistica($stats, 'Fouls'),
                        'tarjetas_amarillas' => $this->buscarEstadistica($stats, 'Yellow Cards'),
                        'tarjetas_rojas' => $this->buscarEstadistica($stats, 'Red Cards'),
                        // 'offsides' => $this->buscarEstadistica($stats, 'Offsides'),
                        // 'ataques' => $this->buscarEstadistica($stats, 'Attacks'),
                        // 'ataques_peligrosos' => $this->buscarEstadistica($stats, 'Dangerous Attacks'),
                    ];
                }
            }
        }

        return [
            'eventos' => $eventos,
            'estadisticas' => $estadisticas,
            'eventos_string' => implode(' | ', $eventos),
            'estadisticas_string' => $this->formatearEstadisticas($estadisticas)
        ];
    }

    /**
     * Busca una estadística específica en el array de estadísticas
     */
    private function buscarEstadistica($stats, $nombre)
    {
        foreach ($stats as $stat) {
            if ($stat['type'] === $nombre) {
                return $stat['value'] ?? 0;
            }
        }
        return 0;
    }

    /**
     * Formatea las estadísticas como string legible
     */
    private function formatearEstadisticas($estadisticas)
    {
        $formato = [];

        foreach ($estadisticas as $equipo => $stats) {
            $equipoStats = [];
            $equipoStats[] = "Posesión: {$stats['posesion']}%";
            $equipoStats[] = "Tiros: {$stats['tiros_totales']} ({$stats['tiros_a_gol']} a gol)";
            $equipoStats[] = "Faltas: {$stats['faltas']}";
            $equipoStats[] = "Tarjetas: 🟨{$stats['tarjetas_amarillas']} 🟥{$stats['tarjetas_rojas']}";
            $equipoStats[] = "Offsides: {$stats['offsides']}";

            $formato[] = "{$equipo}: " . implode(', ', $equipoStats);
        }

        return implode(' | ', $formato);
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

        // 2. Verificar si tiene external_id
        if (!$match->external_id) {
            Log::error("El partido no tiene external_id configurado", [
                'match_id' => $match->id,
                'home_team' => $match->home_team,
                'away_team' => $match->away_team
            ]);
            return null;
        }

        Log::info("Actualizando partido usando external_id:", [
            'id' => $match->id,
            'external_id' => $match->external_id,
            'home_team' => $match->home_team,
            'away_team' => $match->away_team,
            'date' => $match->date,
            'status' => $match->status
        ]);

        // 3. Extraer el fixtureId del external_id
        $fixtureId = $this->extraerFixtureIdDelExternalId($match->external_id);
        Log::info('Fixture ID extraído: ' . $fixtureId);
        if (!$fixtureId) {
            return null;
        }

        // 4. Obtener los datos del fixture directamente
        $fixture = $this->obtenerFixtureDirecto($fixtureId);
        if (!$fixture) {
            Log::error("No se pudo obtener el fixture con ID: $fixtureId");
            return null;
        }

            // Procesar eventos como string legible
            $eventos = [];
            $estadisticas = [];

            if (isset($fixture['events']) && is_array($fixture['events'])) {
                foreach ($fixture['events'] as $evento) {
                    $minuto = $evento['time']['elapsed'] ?? 'N/A';
                    $jugador = $evento['player']['name'] ?? 'N/A';
                    $equipo = $evento['team']['name'] ?? 'N/A';
                    $tipo = $evento['type'] ?? 'N/A';
                    $detalle = $evento['detail'] ?? '';

                    switch ($tipo) {
                        case 'Goal':
                        case 'Goal Penalty':
                        case 'Own Goal':
                            $eventos[] = "⚽ {$minuto}' - {$jugador} ({$equipo}) [{$tipo}]";
                            break;
                        case 'Card':
                            $color = $detalle === 'Yellow Card' ? '🟨' : '🟥';
                            $eventos[] = "{$color} {$minuto}' - {$jugador} ({$equipo}) [{$detalle}]";
                            break;
                        case 'Subst':
                            $eventos[] = "🔄 {$minuto}' - {$jugador} ({$equipo}) [Sustitución]";
                            break;
                        case 'Var':
                            $eventos[] = "📺 {$minuto}' - {$jugador} ({$equipo}) [VAR - {$detalle}]";
                            break;
                        default:
                            $eventos[] = "📊 {$minuto}' - {$jugador} ({$equipo}) [{$tipo}]";
                            break;
                    }
                }
            }

            // Procesar estadísticas del partido
            if (isset($fixture['statistics']) && is_array($fixture['statistics'])) {
                foreach ($fixture['statistics'] as $stat) {
                    if (isset($stat['team']) && isset($stat['statistics'])) {
                        $equipo = $stat['team']['name'];
                        $stats = $stat['statistics'];

                        $estadisticas[$equipo] = [
                            'posesion' => $this->buscarEstadistica($stats, 'Ball Possession'),
                            'tiros_totales' => $this->buscarEstadistica($stats, 'Total Shots'),
                            'tiros_a_gol' => $this->buscarEstadistica($stats, 'Shots on Goal'),
                            'faltas' => $this->buscarEstadistica($stats, 'Fouls'),
                            'tarjetas_amarillas' => $this->buscarEstadistica($stats, 'Yellow Cards'),
                            'tarjetas_rojas' => $this->buscarEstadistica($stats, 'Red Cards'),
                        ];
                    }
                }
            }

            $eventosString = implode(' | ', $eventos);
            $estadisticasString = $this->formatearEstadisticas($estadisticas);

            $match->update([
                'home_team' => $fixture['teams']['home']['name'] ?? null,
                'away_team' => $fixture['teams']['away']['name'] ?? null,
                'date' => $fixture['fixture']['date'] ?? null,
                'status' => $fixture['fixture']['status']['long'] ?? null,
                'score_home' => $fixture['goals']['home'] ?? null,
                'score_away' => $fixture['goals']['away'] ?? null,
                'score' => $fixture['goals']['home'] . ' - ' . $fixture['goals']['away'],
                'events' => $eventosString,
                'statistics' => $estadisticasString,
            ]);
            return $match;
        }
    }
