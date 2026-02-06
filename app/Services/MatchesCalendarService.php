<?php

namespace App\Services;

use App\Models\FootballMatch;
use App\Models\Competition;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MatchesCalendarService
{
    /**
     * API endpoint para Football-Data.org
     */
    protected string $apiBaseUrl = 'https://v3.football.api-sports.io';

    /**
     * API key para Football-Data.org
     */
    protected ?string $apiKey;

    /**
     * Cache duration en minutos
     */
    protected int $cacheDuration = 10;

    public function __construct()
    {
        $this->apiKey = config('services.football_api_key') ?? env('FOOTBALL_API_KEY');
    }

    /**
     * Obtiene partidos agrupados por fecha dentro de un rango
     *
     * @param string|null $fromDate Fecha inicio (YYYY-MM-DD)
     * @param string|null $toDate Fecha fin (YYYY-MM-DD)
     * @param int|null $competitionId ID de competencia
     * @param array|null $teamIds IDs de equipos (opcional)
     * @param bool $includeFinished Incluir partidos finalizados
     *
     * @return array Partidos agrupados por fecha
     */
    public function getMatchesByDate(
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $competitionId = null,
        ?array $teamIds = null,
        bool $includeFinished = true
    ): array {
        // Validar y establecer rangos de fecha
        $fromDate = $this->validateDate($fromDate) ?? Carbon::today()->toDateString();
        $toDate = $this->validateDate($toDate) ?? Carbon::today()->addDays(7)->toDateString();

        // Asegurar que teamIds sea un array
        $teamIds = $teamIds ?? [];

        // Generar clave de caché
        $cacheKey = $this->generateCacheKey('matches', [
            'from' => $fromDate,
            'to' => $toDate,
            'competition' => $competitionId,
            'teams' => implode(',', $teamIds),
            'finished' => $includeFinished
        ]);

        // Intentar obtener del caché
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Obtener partidos de la BD
        $matches = $this->queryMatches(
            $fromDate,
            $toDate,
            $competitionId,
            $teamIds,
            $includeFinished
        );

        // Agrupar por fecha
        $groupedMatches = $this->groupMatchesByDate($matches);

        // Cachear resultado
        Cache::put($cacheKey, $groupedMatches, $this->cacheDuration * 60);

        return $groupedMatches;
    }

    /**
     * Obtiene partidos de una competencia específica
     *
     * @param int $competitionId
     * @param string|null $fromDate
     * @param string|null $toDate
     *
     * @return array
     */
    public function getByCompetition(
        int $competitionId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        return $this->getMatchesByDate($fromDate, $toDate, $competitionId);
    }

    /**
     * Obtiene partidos de equipos específicos
     *
     * @param array $teamIds
     * @param string|null $fromDate
     * @param string|null $toDate
     *
     * @return array
     */
    public function getByTeams(
        array $teamIds,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        return $this->getMatchesByDate($fromDate, $toDate, null, $teamIds);
    }

    /**
     * Construye y ejecuta la query de partidos
     *
     * @param string $fromDate
     * @param string $toDate
     * @param int|null $competitionId
     * @param array $teamIds
     * @param bool $includeFinished
     *
     * @return Collection
     */
    protected function queryMatches(
        string $fromDate,
        string $toDate,
        ?int $competitionId,
        array $teamIds,
        bool $includeFinished
    ): Collection {
        $query = FootballMatch::with(['homeTeam', 'awayTeam', 'competition:id,name']);

        // Buscar por match_date primero, y si no existen resultados, buscar por date
        $dateField = 'match_date';
        $hasMatchDate = FootballMatch::whereNotNull('match_date')->exists();

        if (!$hasMatchDate) {
            $dateField = 'date';
        }

        $query->whereBetween($dateField, [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
            ->orderBy($dateField, 'asc');

        // Filtrar por competencia
        if ($competitionId) {
            $query->where('competition_id', $competitionId);
        }

        // Filtrar por equipos
        if (!empty($teamIds)) {
            $query->where(function ($q) use ($teamIds) {
                $q->whereIn('home_team_id', $teamIds)
                  ->orWhereIn('away_team_id', $teamIds);
            });
        }

        // Filtrar por estado si no incluye finalizados
        if (!$includeFinished) {
            $query->whereIn('status', ['SCHEDULED', 'LIVE']);
        }

        return $query->get();
    }

    /**
     * Agrupa partidos por fecha
     *
     * @param Collection $matches
     *
     * @return array
     */
    protected function groupMatchesByDate(Collection $matches): array
    {
        $grouped = [];

        // Obtener zona horaria del usuario autenticado
        $userTimezone = auth()->check() ? (auth()->user()->timezone ?? config('app.timezone')) : config('app.timezone');

        foreach ($matches as $match) {
            // Usar match_date si existe, si no usar date
            $dateField = $match->match_date ?? $match->date;

            // Convertir a zona horaria del usuario ANTES de agrupar
            $matchDate = Carbon::parse($dateField)->setTimezone($userTimezone);
            $date = $matchDate->toDateString();

            if (!isset($grouped[$date])) {
                $grouped[$date] = [];
            }

            $grouped[$date][] = $this->formatMatch($match);
        }

        // Ordenar por fecha
        ksort($grouped);

        return $grouped;
    }

    /**
     * Formatea un partido para la respuesta
     *
     * @param FootballMatch $match
     *
     * @return array
     */
    protected function formatMatch(FootballMatch $match): array
    {
        // Usar match_date si existe, si no usar date
        $dateField = $match->match_date ?? $match->date;
        $matchDate = Carbon::parse($dateField);

        // Convertir a zona horaria del usuario autenticado
        $userTimezone = auth()->check() ? (auth()->user()->timezone ?? config('app.timezone')) : config('app.timezone');
        $matchDateUser = $matchDate->setTimezone($userTimezone);

        return [
            'id' => $match->id,
            'external_id' => $match->external_id,
            'home_team' => [
                'id' => $match->home_team_id,
                'name' => $match->homeTeam?->name ?? $match->home_team,
                'crest_url' => $match->homeTeam?->crest_url,
            ],
            'away_team' => [
                'id' => $match->away_team_id,
                'name' => $match->awayTeam?->name ?? $match->away_team,
                'crest_url' => $match->awayTeam?->crest_url,
            ],
            'kick_off_time' => $matchDateUser->format('H:i'),
            'kick_off_timestamp' => $matchDate->timestamp,
            'status' => $match->status,
            'score' => [
                'home' => $match->home_team_score,
                'away' => $match->away_team_score,
            ],
            'penalties' => [
                'home' => $match->home_team_penalties,
                'away' => $match->away_team_penalties,
            ],
            'competition' => [
                'id' => $match->competition?->id,
                'name' => $match->competition?->name ?? $match->league,
            ],
            'stage' => $match->matchday,
        ];
    }

    /**
     * Obtiene todas las competencias disponibles en la BD
     * que tienen partidos
     *
     * @return Collection
     */
    public function getAvailableCompetitions(): array
    {
        $cacheKey = 'available_competitions';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Obtener de la tabla competitions si existen registros
        $competitions = Competition::whereHas('matches')
            ->select('id', 'name', 'type', 'country')
            ->orderBy('name')
            ->get()
            ->map(fn($comp) => [
                'id' => $comp->id,
                'name' => $comp->name,
                'type' => $comp->type ?? 'league',
                'country' => $comp->country,
            ])
            ->toArray();

        // Si no hay competiciones en la tabla, usar las ligas del campo 'league'
        if (empty($competitions)) {
            $leagues = FootballMatch::whereNotNull('league')
                ->distinct('league')
                ->orderBy('league')
                ->pluck('league')
                ->toArray();

            // Mapear códigos de liga a nombres legibles
            $leagueNames = [
                'PL' => 'Premier League',
                'PD' => 'La Liga',
                'SA' => 'Serie A',
                'BL1' => 'Bundesliga',
                'FL1' => 'Ligue 1',
                'DED' => 'Eredivisie',
                'PPL' => 'Primeira Liga',
                'CL' => 'UEFA Champions League',
                'ELC' => 'UEFA Europa League',
                'fa-cup' => 'FA Cup',
                'serie-a' => 'Serie A',
                'la-liga' => 'La Liga',
                'bundesliga' => 'Bundesliga',
                'ligue-1' => 'Ligue 1',
                'primera-division' => 'La Liga',
                'eredivisie' => 'Eredivisie',
                'primeira-liga' => 'Primeira Liga',
                'serie-b' => 'Serie B',
                'championship' => 'Championship',
                'champions-league' => 'Champions League',
            ];

            $competitions = array_map(function ($index, $league) use ($leagueNames) {
                return [
                    'id' => $index + 1,
                    'name' => $leagueNames[$league] ?? ucwords(str_replace('-', ' ', $league)),
                    'type' => 'league',
                    'country' => null,
                ];
            }, array_keys($leagues), $leagues);
        }

        Cache::put($cacheKey, $competitions, 60 * 60); // Cache 1 hora

        return $competitions;
    }

    /**
     * Obtiene todos los equipos disponibles en la BD
     *
     * @return Collection
     */
    public function getAvailableTeams(): Collection
    {
        $cacheKey = 'available_teams';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $teams = Team::select('id', 'name', 'crest_url')
            ->orderBy('name')
            ->get();

        Cache::put($cacheKey, $teams, 60 * 60); // Cache 1 hora

        return $teams;
    }

    /**
     * Sincroniza partidos desde la API externa
     *
     * @param int $competitionId ID de competencia
     * @param int $leagueId ID de liga en la API-Sports
     * @param int $season Temporada
     *
     * @return array
     */
    public function syncFromExternalAPI(
        int $competitionId,
        int $leagueId,
        int $season
    ): array {
        $competition = Competition::find($competitionId);
        if (!$competition) {
            return [
                'success' => false,
                'message' => 'Competencia no encontrada',
                'synced' => 0
            ];
        }

        try {
            $fromDate = Carbon::today()->toDateString();
            $toDate = Carbon::today()->addDays(30)->toDateString();

            $matches = $this->fetchFromAPIFootballSports(
                $fromDate,
                $toDate,
                $leagueId,
                $season
            );

            if (!$matches) {
                return [
                    'success' => false,
                    'message' => 'No se obtuvieron datos de la API',
                    'synced' => 0
                ];
            }

            $synced = $this->saveMatches($matches, $competition);

            // Invalidar caché
            $this->invalidateMatchesCache();

            return [
                'success' => true,
                'message' => "Sincronizados {$synced} partidos",
                'synced' => $synced
            ];

        } catch (\Exception $e) {
            Log::error('Error sincronizando partidos de API externa', [
                'competition_id' => $competitionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error al sincronizar: ' . $e->getMessage(),
                'synced' => 0
            ];
        }
    }

    /**
     * Obtiene partidos desde la API externa de football-data.org
     *
     * @param string $fromDate
     * @param string $toDate
     * @param int $leagueId
     * @param int $season
     *
     * @return array|null
     */
    protected function fetchFromAPIFootballSports(
        string $fromDate,
        string $toDate,
        int $leagueId,
        int $season
    ): ?array {
        try {
            if (!$this->apiKey) {
                Log::error('API key no configurada', [
                    'key_sources' => ['config(services.football_api_key)', 'env(FOOTBALL_API_KEY)']
                ]);
                return null;
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'X-RapidAPI-Key' => $this->apiKey,
                    'X-RapidAPI-Host' => 'v3.football.api-sports.io',
                ])
                ->get("$this->apiBaseUrl/fixtures", [
                    'league' => $leagueId,
                    'season' => $season,
                    'from' => $fromDate,
                    'to' => $toDate,
                ]);

            if (!$response->successful()) {
                Log::warning('API Sports retornó error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            return $response->json('response');

        } catch (\Exception $e) {
            Log::error('Error conectando a API Sports', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Guarda partidos en la base de datos
     *
     * @param array $apiMatches
     * @param Competition $competition
     *
     * @return int Cantidad de partidos guardados/actualizados
     */
    protected function saveMatches(array $apiMatches, Competition $competition): int
    {
        $synced = 0;

        foreach ($apiMatches as $apiMatch) {
            try {
                $matchData = $this->transformAPIMatch($apiMatch, $competition);

                FootballMatch::updateOrCreate(
                    ['external_id' => $apiMatch['fixture']['id']],
                    $matchData
                );

                $synced++;

            } catch (\Exception $e) {
                Log::warning('Error guardando partido', [
                    'external_id' => $apiMatch['fixture']['id'] ?? null,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        return $synced;
    }

    /**
     * Transforma datos de API externa al formato de nuestra BD
     *
     * @param array $apiMatch
     * @param Competition $competition
     *
     * @return array
     */
    protected function transformAPIMatch(array $apiMatch, Competition $competition): array
    {
        $fixture = $apiMatch['fixture'];
        $teams = $apiMatch['teams'];
        $goals = $apiMatch['goals'];
        $score = $apiMatch['score'];

        return [
            'external_id' => $fixture['id'],
            'home_team' => $teams['home']['name'],
            'away_team' => $teams['away']['name'],
            'match_date' => Carbon::parse($fixture['date']),
            'status' => $fixture['status'],
            'league' => $competition->type,
            'competition_id' => $competition->id,
            'home_team_score' => $goals['home'],
            'away_team_score' => $goals['away'],
            'home_team_penalties' => $score['penalty']['home'],
            'away_team_penalties' => $score['penalty']['away'],
            'matchday' => $fixture['round'] ?? null,
        ];
    }

    /**
     * Valida una fecha en formato YYYY-MM-DD
     *
     * @param string|null $date
     *
     * @return string|null
     */
    protected function validateDate(?string $date): ?string
    {
        if (!$date) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $date)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Genera clave para caché
     *
     * @param string $prefix
     * @param array $params
     *
     * @return string
     */
    protected function generateCacheKey(string $prefix, array $params): string
    {
        $hash = md5(json_encode($params));
        return "matches_calendar:{$prefix}:{$hash}";
    }

    /**
     * Invalida caché de partidos
     */
    protected function invalidateMatchesCache(): void
    {
        Cache::flush(); // O más selectivamente si prefieres
        // Cache::forget('available_competitions');
        // Cache::forget('available_teams');
    }

    /**
     * Obtiene estadísticas de partidos para un período
     *
     * @param string|null $fromDate
     * @param string|null $toDate
     * @param int|null $competitionId
     *
     * @return array
     */
    public function getStatistics(
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $competitionId = null
    ): array {
        $fromDate = $this->validateDate($fromDate) ?? Carbon::today()->toDateString();
        $toDate = $this->validateDate($toDate) ?? Carbon::today()->addDays(7)->toDateString();

        $query = FootballMatch::whereBetween('match_date', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);

        if ($competitionId) {
            $query->where('competition_id', $competitionId);
        }

        $total = $query->count();
        $scheduled = $query->clone()->where('status', 'SCHEDULED')->count();
        $live = $query->clone()->where('status', 'LIVE')->count();
        $finished = $query->clone()->where('status', 'FINISHED')->count();

        return [
            'total' => $total,
            'scheduled' => $scheduled,
            'live' => $live,
            'finished' => $finished,
        ];
    }
}
