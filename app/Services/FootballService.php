<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\FootballMatch;
use App\Exceptions\FootballApiException;

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
            throw new FootballApiException(
                "Competencia no soportada: $competition",
                ['competition' => $competition, 'available_competitions' => array_keys($this->leagueMap)]
            );
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
            throw new FootballApiException(
                'Error al obtener los partidos desde la API externa',
                [
                    'competition' => $competition,
                    'league_id' => $leagueId,
                    'season' => 2025,
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]
            );
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

    public function getMatches($competitionId, $forceRefresh = false)
    {
        $cacheKey = "matches_{$competitionId}";

        // Si se fuerza el refresh, limpiar el cache
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($competitionId) {
            // Obtener la temporada correcta basada en la fecha actual
            $currentSeason = $this->getSeasonForDate();

            Log::info("Obteniendo partidos para competencia $competitionId, temporada calculada: $currentSeason");

            // Intentar primero con la temporada calculada
            $response = Http::withHeaders([
                'X-Auth-Token' => config('services.football_data.api_key'),
            ])->get("http://api.football-data.org/v4/competitions/{$competitionId}/matches", [
                'season' => $currentSeason
            ]);

            // Si no hay datos en la temporada calculada, intentar con la temporada anterior
            if (!$response->successful() || empty($response->json()['matches'])) {
                $previousSeason = $currentSeason - 1;
                Log::info("No se encontraron partidos en temporada $currentSeason, intentando con temporada anterior: $previousSeason");

                $response = Http::withHeaders([
                    'X-Auth-Token' => config('services.football_data.api_key'),
                ])->get("http://api.football-data.org/v4/competitions/{$competitionId}/matches", [
                    'season' => $previousSeason
                ]);
            } else {
                Log::info("Partidos encontrados en temporada calculada: " . count($response->json()['matches'] ?? []));
            }

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

    /**
     * Obtiene partidos para una competencia específica en una fecha específica
     */
    public function getMatchesByDate($competitionId, $date, $forceRefresh = false)
    {
        $cacheKey = "matches_{$competitionId}_{$date}";

        // Si se fuerza el refresh, limpiar el cache
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($competitionId, $date) {
            // Obtener la temporada correcta basada en la fecha del partido
            $season = $this->getSeasonForDate($date);

            Log::info("Obteniendo partidos para competencia $competitionId en fecha $date, temporada: $season");

            $response = Http::withHeaders([
                'X-Auth-Token' => config('services.football_data.api_key'),
            ])->get("http://api.football-data.org/v4/competitions/{$competitionId}/matches", [
                'season' => $season,
                'dateFrom' => $date,
                'dateTo' => $date
            ]);

            if ($response->successful()) {
                $matches = collect($response->json()['matches'])->map(function ($match) {
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

                Log::info("Partidos encontrados para fecha $date: " . $matches->count());
                return $matches;
            }

            Log::warning("No se encontraron partidos para fecha $date");
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
     * Determina la temporada correcta basada en la fecha del partido
     * Para la mayoría de ligas europeas, la temporada va de agosto a julio
     */
    private function getSeasonForDate($matchDate = null)
    {
        if (!$matchDate) {
            $matchDate = now();
        }

        if (is_string($matchDate)) {
            $matchDate = \Carbon\Carbon::parse($matchDate);
        }

        $year = $matchDate->year;
        $month = $matchDate->month;

        // Para ligas que van de agosto a julio (temporada europea)
        // Si estamos entre enero y julio, usar el año anterior como temporada
        if ($month >= 1 && $month <= 7) {
            return $year - 1;
        }

        return $year;
    }

    /**
     * Determina si una liga es latinoamericana (con dos torneos por año)
     */
    private function isLatinAmericanLeague($competition)
    {
        $latinAmericanLeagues = [
            'liga-colombia',
            // 'chile-campeonato-nacional',
            'liga-argentina',
            'liga-mexicana',
            'liga-brasilera'
        ];

        return in_array($competition, $latinAmericanLeagues);
    }

    /**
     * Determina el torneo (Apertura/Clausura) basado en la fecha
     * Para ligas latinoamericanas que tienen dos torneos por año
     */
    private function getTournamentType($matchDate)
    {
        if (is_string($matchDate)) {
            $matchDate = \Carbon\Carbon::parse($matchDate);
        }

        $month = $matchDate->month;

        // Apertura: generalmente de enero/febrero a junio/julio
        // Clausura: generalmente de julio/agosto a diciembre
        if ($month >= 1 && $month <= 6) {
            return 'Apertura';
        } else {
            return 'Clausura';
        }
    }

            /**
     * Extrae el fixtureId del external_id almacenado
     * El external_id puede tener diferentes formatos:
     * 1. Número directo (fixture ID): "123456"
     * 2. Formato con equipos y fecha: "Bucaramanga_Once Caldas_2025-07-22T20:00:00+00:00"
     */
    public function extraerFixtureIdDelExternalId($externalId, $matchDate = null)
    {
        Log::info("Extrayendo fixtureId del external_id: $externalId, fecha del partido: $matchDate");

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

                // Usar la fecha del partido si está disponible, sino usar la fecha del external_id
                $searchDate = $matchDate ? $matchDate : $date->format('Y-m-d');

                // Intentar con diferentes competencias en orden de prioridad
                $competitions = ['liga-colombia', 'champions-league', 'premier-league', 'la-liga'];

                foreach ($competitions as $competition) {
                    Log::info("Intentando buscar fixture en competencia: $competition con fecha: $searchDate");
                    $fixtureId = $this->buscarFixtureId($competition, $season, $homeTeam, $awayTeam, $searchDate);
                    if ($fixtureId) {
                        Log::info("Fixture encontrado en $competition: $fixtureId");
                        return $fixtureId;
                    }
                }

                // Si no se encontró con filtros de fecha, intentar sin filtros para ligas latinoamericanas
                foreach ($competitions as $competition) {
                    if ($this->isLatinAmericanLeague($competition)) {
                        Log::info("Intentando búsqueda sin filtros de fecha para liga latinoamericana: $competition");
                        $fixtureId = $this->buscarFixtureIdLatinoamericano($competition, $season, $homeTeam, $awayTeam);
                        if ($fixtureId) {
                            Log::info("Fixture encontrado sin filtros en $competition: $fixtureId");
                            return $fixtureId;
                        }
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
    public function obtenerFixtureDirecto($fixtureId)
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
     * Busca el fixtureId de un partido terminado por nombres de equipos, liga, temporada y fecha
     */
    public function buscarFixtureId($competition, $season, $homeTeam, $awayTeam, $matchDate = null)
    {
        $leagueId = $this->leagueMap[$competition] ?? null;
        if (!$leagueId) {
            Log::warning("Competencia no soportada: $competition, usando Champions League como fallback");
            $leagueId = $this->leagueMap['champions-league'];
        }

        Log::info("Buscando fixture para: $homeTeam vs $awayTeam en liga $competition (ID: $leagueId), temporada $season, fecha: $matchDate");

        // Si tenemos la fecha del partido, usarla para filtrar más específicamente
        $dateFrom = null;
        $dateTo = null;

        if ($matchDate) {
            $matchDateObj = \Carbon\Carbon::parse($matchDate);

                        // Para ligas latinoamericanas, usar un rango más amplio para encontrar el partido
            if ($this->isLatinAmericanLeague($competition)) {
                $tournamentType = $this->getTournamentType($matchDate);
                Log::info("Liga latinoamericana detectada, torneo: $tournamentType");

                // Usar un rango más amplio para ligas latinoamericanas
                $dateFrom = $matchDateObj->subDays(7)->format('Y-m-d');
                $dateTo = $matchDateObj->addDays(7)->format('Y-m-d');
            } else {
                // Para ligas europeas, mantener el rango más amplio
                $dateFrom = $matchDateObj->subDays(3)->format('Y-m-d');
                $dateTo = $matchDateObj->addDays(6)->format('Y-m-d'); // +6 porque ya restamos 3
            }
        }

        // Implementar retry con delay para evitar rate limiting
        $maxRetries = 3;
        $delaySeconds = 2;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            // Aplicar delay aleatorio antes de cada request
            $this->applyRateLimitDelay(1, 2);

            $params = [
                'league' => $leagueId,
                'season' => $season,
                'status' => 'FT'
            ];

            // Agregar filtros de fecha si están disponibles
            if ($dateFrom && $dateTo) {
                $params['date'] = $dateFrom;
                $params['dateTo'] = $dateTo;
            }

            $response = Http::withHeaders([
                'X-RapidAPI-Key' => $this->apiKey,
                'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
            ])->get($this->baseUrl . 'fixtures', $params);

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

                // Ordenar fixtures por fecha para priorizar el más cercano a la fecha del partido
        // Pero solo si tenemos fecha del partido y no es una liga latinoamericana
        if ($matchDate && !empty($fixtures) && !$this->isLatinAmericanLeague($competition)) {
            $matchDateObj = \Carbon\Carbon::parse($matchDate);
            usort($fixtures, function($a, $b) use ($matchDateObj) {
                $dateA = \Carbon\Carbon::parse($a['fixture']['date']);
                $dateB = \Carbon\Carbon::parse($b['fixture']['date']);

                $diffA = abs($dateA->diffInDays($matchDateObj));
                $diffB = abs($dateB->diffInDays($matchDateObj));

                return $diffA <=> $diffB;
            });
        }

        foreach ($fixtures as $fixture) {
            $home = strtolower($fixture['teams']['home']['name']);
            $away = strtolower($fixture['teams']['away']['name']);
            $homeTeamLower = strtolower($homeTeam);
            $awayTeamLower = strtolower($awayTeam);
            $fixtureDate = $fixture['fixture']['date'] ?? null;

            Log::info("Comparando: '$home' vs '$homeTeamLower' y '$away' vs '$awayTeamLower', fecha fixture: $fixtureDate");

            // Búsqueda más flexible: verificar ambas combinaciones
            $match1 = str_contains($home, $homeTeamLower) && str_contains($away, $awayTeamLower);
            $match2 = str_contains($home, $awayTeamLower) && str_contains($away, $homeTeamLower);

            if ($match1 || $match2) {
                                // Para ligas latinoamericanas, recolectar todos los partidos y tomar el más reciente
                if ($this->isLatinAmericanLeague($competition)) {
                    // Si es la primera vez que encontramos un partido, recolectar todos
                    if (!isset($matchingFixtures)) {
                        $matchingFixtures = [];
                    }

                    $matchingFixtures[] = [
                        'fixture' => $fixture,
                        'date' => $fixtureDate,
                        'status' => $fixture['fixture']['status']['long'] ?? 'N/A',
                        'id' => $fixture['fixture']['id']
                    ];
                    Log::info("Partido encontrado para liga latinoamericana: ID " . $fixture['fixture']['id'] . ", fecha: $fixtureDate");
                    continue; // Continuar buscando más partidos
                }

                // Para otras ligas, verificar que la fecha sea cercana
                if ($matchDate && $fixtureDate) {
                    $matchDateObj = \Carbon\Carbon::parse($matchDate);
                    $fixtureDateObj = \Carbon\Carbon::parse($fixtureDate);
                    $daysDiff = abs($matchDateObj->diffInDays($fixtureDateObj));

                    if ($daysDiff <= 3) {
                        Log::info("¡Fixture encontrado con fecha cercana! ID: " . $fixture['fixture']['id'] . ", diferencia de días: $daysDiff");
                        return $fixture['fixture']['id'];
                    } else {
                        Log::info("Fixture encontrado pero fecha muy diferente (diferencia: $daysDiff días), continuando búsqueda...");
                        continue;
                    }
                } else {
                    Log::info("¡Fixture encontrado! ID: " . $fixture['fixture']['id']);
                    return $fixture['fixture']['id'];
                }
            }
        }

        // Para ligas latinoamericanas, procesar todos los partidos encontrados
        if ($this->isLatinAmericanLeague($competition) && isset($matchingFixtures) && !empty($matchingFixtures)) {
            // Ordenar por fecha (más reciente primero)
            usort($matchingFixtures, function($a, $b) {
                $dateA = \Carbon\Carbon::parse($a['date']);
                $dateB = \Carbon\Carbon::parse($b['date']);
                return $dateB->timestamp - $dateA->timestamp; // Orden descendente (más reciente primero)
            });

            // Tomar el más reciente
            $mostRecent = $matchingFixtures[0];
            Log::info("Seleccionando el partido más reciente para liga latinoamericana: ID " . $mostRecent['id'] . ", fecha: " . $mostRecent['date'] . ", status: " . $mostRecent['status']);

            if (count($matchingFixtures) > 1) {
                Log::info("Se encontraron " . count($matchingFixtures) . " partidos entre estos equipos:");
                foreach ($matchingFixtures as $index => $match) {
                    $tournamentType = $this->getTournamentType($match['date']);
                    Log::info("  " . ($index + 1) . ". ID: " . $match['id'] . ", fecha: " . $match['date'] . ", torneo: $tournamentType, status: " . $match['status']);
                }
            }

            return $mostRecent['id'];
        }

        Log::warning("No se encontró fixture para: $homeTeam vs $awayTeam");

        // Intentar búsqueda sin filtro de estado
        Log::info("Intentando búsqueda sin filtro de estado...");

        $maxRetries2 = 3;
        $delaySeconds2 = 2;

        for ($attempt2 = 1; $attempt2 <= $maxRetries2; $attempt2++) {
            // Aplicar delay aleatorio antes de cada request
            $this->applyRateLimitDelay(1, 2);

            $params2 = [
                'league' => $leagueId,
                'season' => $season
            ];

            // Agregar filtros de fecha si están disponibles
            if ($dateFrom && $dateTo) {
                $params2['date'] = $dateFrom;
                $params2['dateTo'] = $dateTo;
            }

            $response2 = Http::withHeaders([
                'X-RapidAPI-Key' => $this->apiKey,
                'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
            ])->get($this->baseUrl . 'fixtures', $params2);

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

                        // Ordenar fixtures por fecha si tenemos fecha del partido y no es liga latinoamericana
            if ($matchDate && !empty($fixtures2) && !$this->isLatinAmericanLeague($competition)) {
                $matchDateObj = \Carbon\Carbon::parse($matchDate);
                usort($fixtures2, function($a, $b) use ($matchDateObj) {
                    $dateA = \Carbon\Carbon::parse($a['fixture']['date']);
                    $dateB = \Carbon\Carbon::parse($b['fixture']['date']);

                    $diffA = abs($dateA->diffInDays($matchDateObj));
                    $diffB = abs($dateB->diffInDays($matchDateObj));

                    return $diffA <=> $diffB;
                });
            }

            foreach ($fixtures2 as $fixture) {
                $home = strtolower($fixture['teams']['home']['name']);
                $away = strtolower($fixture['teams']['away']['name']);
                $homeTeamLower = strtolower($homeTeam);
                $awayTeamLower = strtolower($awayTeam);
                $fixtureDate = $fixture['fixture']['date'] ?? null;

                $match1 = str_contains($home, $homeTeamLower) && str_contains($away, $awayTeamLower);
                $match2 = str_contains($home, $awayTeamLower) && str_contains($away, $homeTeamLower);

                if ($match1 || $match2) {
                                        // Para ligas latinoamericanas, recolectar todos los partidos y tomar el más reciente
                    if ($this->isLatinAmericanLeague($competition)) {
                        // Si es la primera vez que encontramos un partido, recolectar todos
                        if (!isset($matchingFixtures2)) {
                            $matchingFixtures2 = [];
                        }

                        $matchingFixtures2[] = [
                            'fixture' => $fixture,
                            'date' => $fixtureDate,
                            'status' => $fixture['fixture']['status']['long'] ?? 'N/A',
                            'id' => $fixture['fixture']['id']
                        ];
                        Log::info("Partido encontrado sin filtro para liga latinoamericana: ID " . $fixture['fixture']['id'] . ", fecha: $fixtureDate");
                        continue; // Continuar buscando más partidos
                    }

                    // Para otras ligas, verificar que la fecha sea cercana
                    if ($matchDate && $fixtureDate) {
                        $matchDateObj = \Carbon\Carbon::parse($matchDate);
                        $fixtureDateObj = \Carbon\Carbon::parse($fixtureDate);
                        $daysDiff = abs($matchDateObj->diffInDays($fixtureDateObj));

                        if ($daysDiff <= 3) {
                            Log::info("¡Fixture encontrado sin filtro de estado con fecha cercana! ID: " . $fixture['fixture']['id'] . " Status: " . $fixture['fixture']['status']['long'] . ", diferencia de días: $daysDiff");
                            return $fixture['fixture']['id'];
                        } else {
                            Log::info("Fixture encontrado sin filtro pero fecha muy diferente (diferencia: $daysDiff días), continuando búsqueda...");
                            continue;
                        }
                    } else {
                        Log::info("¡Fixture encontrado sin filtro de estado! ID: " . $fixture['fixture']['id'] . " Status: " . $fixture['fixture']['status']['long']);
                        return $fixture['fixture']['id'];
                    }
                }
            }
        }

        // Para ligas latinoamericanas, procesar todos los partidos encontrados en la segunda búsqueda
        if ($this->isLatinAmericanLeague($competition) && isset($matchingFixtures2) && !empty($matchingFixtures2)) {
            // Ordenar por fecha (más reciente primero)
            usort($matchingFixtures2, function($a, $b) {
                $dateA = \Carbon\Carbon::parse($a['date']);
                $dateB = \Carbon\Carbon::parse($b['date']);
                return $dateB->timestamp - $dateA->timestamp; // Orden descendente (más reciente primero)
            });

            // Tomar el más reciente
            $mostRecent = $matchingFixtures2[0];
            Log::info("Seleccionando el partido más reciente sin filtro para liga latinoamericana: ID " . $mostRecent['id'] . ", fecha: " . $mostRecent['date'] . ", status: " . $mostRecent['status']);

            if (count($matchingFixtures2) > 1) {
                Log::info("Se encontraron " . count($matchingFixtures2) . " partidos sin filtro entre estos equipos:");
                foreach ($matchingFixtures2 as $index => $match) {
                    $tournamentType = $this->getTournamentType($match['date']);
                    Log::info("  " . ($index + 1) . ". ID: " . $match['id'] . ", fecha: " . $match['date'] . ", torneo: $tournamentType, status: " . $match['status']);
                }
            }

            return $mostRecent['id'];
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
            // $equipoStats[] = "Offsides: {$stats['offsides']}";

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

        // 3. Extraer el fixtureId del external_id usando la fecha del partido
        $matchDate = $match->date ? $match->date->format('Y-m-d') : null;
        $fixtureId = $this->extraerFixtureIdDelExternalId($match->external_id, $matchDate);
        Log::info('Fixture ID extraído: ' . $fixtureId . ' para fecha: ' . $matchDate);
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
                // 'date' => $fixture['fixture']['date'] ?? null,
                'status' => $fixture['fixture']['status']['long'] ?? null,
                'score_home' => $fixture['goals']['home'] ?? null,
                'score_away' => $fixture['goals']['away'] ?? null,
                'score' => $fixture['goals']['home'] . ' - ' . $fixture['goals']['away'],
                'events' => $eventosString,
                'statistics' => $estadisticasString,
            ]);
            return $match;
        }

    /**
     * Busca el fixtureId específicamente para ligas latinoamericanas sin filtros de fecha
     * Obtiene todos los partidos entre los equipos y retorna el más reciente
     */
    public function buscarFixtureIdLatinoamericano($competition, $season, $homeTeam, $awayTeam)
    {
        $leagueId = $this->leagueMap[$competition] ?? null;
        if (!$leagueId) {
            Log::warning("Competencia no soportada: $competition");
            return null;
        }

        Log::info("Buscando fixture latinoamericano para: $homeTeam vs $awayTeam en liga $competition (ID: $leagueId), temporada $season");

        // Implementar retry con delay para evitar rate limiting
        $maxRetries = 3;
        $delaySeconds = 2;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $this->applyRateLimitDelay(1, 2);

            $response = Http::withHeaders([
                'X-RapidAPI-Key' => $this->apiKey,
                'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
            ])->get($this->baseUrl . 'fixtures', [
                'league' => $leagueId,
                'season' => $season
            ]);

            if ($response->successful()) {
                break;
            }

            if (str_contains($response->body(), 'Too many requests')) {
                Log::warning("Rate limit alcanzado en intento $attempt. Esperando $delaySeconds segundos...");
                if ($attempt < $maxRetries) {
                    sleep($delaySeconds);
                    $delaySeconds *= 2;
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
        Log::info("Fixtures encontrados sin filtros: " . count($fixtures));

        // Buscar todos los partidos entre estos equipos
        $matchingFixtures = [];
        foreach ($fixtures as $fixture) {
            $home = strtolower($fixture['teams']['home']['name']);
            $away = strtolower($fixture['teams']['away']['name']);
            $homeTeamLower = strtolower($homeTeam);
            $awayTeamLower = strtolower($awayTeam);
            $fixtureDate = $fixture['fixture']['date'] ?? null;
            $status = $fixture['fixture']['status']['long'] ?? 'N/A';

            // Búsqueda más flexible: verificar ambas combinaciones
            $match1 = str_contains($home, $homeTeamLower) && str_contains($away, $awayTeamLower);
            $match2 = str_contains($home, $awayTeamLower) && str_contains($away, $homeTeamLower);

            if ($match1 || $match2) {
                $matchingFixtures[] = [
                    'fixture' => $fixture,
                    'date' => $fixtureDate,
                    'status' => $status,
                    'id' => $fixture['fixture']['id']
                ];
                Log::info("Partido encontrado: ID " . $fixture['fixture']['id'] . ", fecha: $fixtureDate, status: $status");
            }
        }

        if (empty($matchingFixtures)) {
            Log::warning("No se encontraron partidos para liga latinoamericana: $homeTeam vs $awayTeam");
            return null;
        }

                    // Ordenar por fecha (más reciente primero)
            usort($matchingFixtures, function($a, $b) {
                $dateA = \Carbon\Carbon::parse($a['date']);
                $dateB = \Carbon\Carbon::parse($b['date']);
                return $dateB->timestamp - $dateA->timestamp; // Orden descendente (más reciente primero)
            });

        // Tomar el más reciente
        $mostRecent = $matchingFixtures[0];
        Log::info("Seleccionando el partido más reciente: ID " . $mostRecent['id'] . ", fecha: " . $mostRecent['date'] . ", status: " . $mostRecent['status']);

        if (count($matchingFixtures) > 1) {
            Log::info("Se encontraron " . count($matchingFixtures) . " partidos entre estos equipos:");
            foreach ($matchingFixtures as $index => $match) {
                $tournamentType = $this->getTournamentType($match['date']);
                Log::info("  " . ($index + 1) . ". ID: " . $match['id'] . ", fecha: " . $match['date'] . ", torneo: $tournamentType, status: " . $match['status']);
            }
        }

        return $mostRecent['id'];
    }
}
