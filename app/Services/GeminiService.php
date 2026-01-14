<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

class GeminiService
{
    protected $apiKey;
    protected $model;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
    protected $maxRetries;
    protected $retryDelay;
    protected $groundingEnabled;
    protected $timeout;

    public function __construct()
    {
        // En testing, allow no API key
        if (app()->environment('testing')) {
            $this->apiKey = config('gemini.api_key', 'test_key');
            $this->model = config('gemini.model', 'gemini-2.5-flash');
            $this->groundingEnabled = false;
        } else {
            $this->apiKey = config('gemini.api_key');
            $this->model = config('gemini.model', 'gemini-3-pro-preview');
        }

        $this->maxRetries = config('gemini.max_retries', 5);
        $this->retryDelay = config('gemini.retry_delay', 2);
        $this->groundingEnabled = config('gemini.grounding_enabled', true);
        $this->timeout = config('gemini.timeout', 60);

        if (!app()->environment('testing') && !$this->apiKey) {
            throw new Exception('GEMINI_API_KEY no configurada en .env');
        }
    }

    /**
     * Obtener fixtures (calendario) de una liga con grounding
     */
    public function getFixtures($league, $forceRefresh = false)
    {
        $cacheKey = "gemini_fixtures_{$league}";

        if (!$forceRefresh && Cache::has($cacheKey)) {
            Log::debug("Fixtures para {$league} obtenidas del caché");
            return Cache::get($cacheKey);
        }

        $prompt = $this->buildFixturesPrompt($league);
        $result = $this->callGemini($prompt, $this->groundingEnabled);

        if ($result) {
            $ttl = config('gemini.cache.fixtures_ttl', 24 * 60);
            Cache::put($cacheKey, $result, now()->addMinutes($ttl));
            Log::info("Fixtures para {$league} obtenidas y cacheadas");
        }

        return $result;
    }

    /**
     * Obtener resultados de una liga para una fecha específica
     */
    public function getResults($league, $date = null, $forceRefresh = false)
    {
        $date = $date ?? now()->format('Y-m-d');
        $cacheKey = "gemini_results_{$league}_{$date}";

        if (!$forceRefresh && Cache::has($cacheKey)) {
            Log::debug("Resultados para {$league} en {$date} obtenidos del caché");
            return Cache::get($cacheKey);
        }

        $prompt = $this->buildResultsPrompt($league, $date);
        $result = $this->callGemini($prompt, $this->groundingEnabled);

        if ($result) {
            $ttl = config('gemini.cache.results_ttl', 48 * 60);
            Cache::put($cacheKey, $result, now()->addMinutes($ttl));
            Log::info("Resultados para {$league} en {$date} obtenidos y cacheados");
        }

        return $result;
    }

    /**
     * Analizar un partido con Gemini
     */
    public function analyzeMatch($homeTeam, $awayTeam, $date, $forceRefresh = false)
    {
        $cacheKey = "gemini_analysis_{$homeTeam}_{$awayTeam}_{$date}";

        if (!$forceRefresh && Cache::has($cacheKey)) {
            Log::debug("Análisis de {$homeTeam} vs {$awayTeam} obtenido del caché");
            return Cache::get($cacheKey);
        }

        $prompt = $this->buildAnalysisPrompt($homeTeam, $awayTeam, $date);
        $result = $this->callGemini($prompt, $this->groundingEnabled);

        if ($result) {
            $ttl = config('gemini.cache.analysis_ttl', 72 * 60);
            Cache::put($cacheKey, $result, now()->addMinutes($ttl));
            Log::info("Análisis de {$homeTeam} vs {$awayTeam} obtenido y cacheado");
        }

        return $result;
    }

    /**
     * Obtener el resultado real de un partido específico
     * Usa Gemini con grounding para buscar el resultado en internet
     */
    public function getMatchResult($homeTeam, $awayTeam, $date, $league = null, $forceRefresh = false)
    {
        $cacheKey = "gemini_match_result_" . md5("{$homeTeam}_{$awayTeam}_{$date}");

        if (!$forceRefresh && Cache::has($cacheKey)) {
            Log::info("Resultado en caché para {$homeTeam} vs {$awayTeam} ({$date})");
            return Cache::get($cacheKey);
        }

        // Construir prompt para obtener resultado
        $dateFormatted = Carbon::parse($date)->format('d de F de Y');
        $leagueInfo = $league ? " en la liga de {$league}" : "";

        $prompt = "¿Cuál fue el resultado final del partido entre {$homeTeam} y {$awayTeam} que se jugó el {$dateFormatted}{$leagueInfo}? "
                . "Responde SOLO con el marcador en formato: 'HOME_GOALS - AWAY_GOALS' (ejemplo: '3 - 2'). "
                . "Si el partido no ha terminado, responde con 'NO_JUGADO'. "
                . "Si no encuentras información, responde con 'NO_ENCONTRADO'.";

        Log::info("Consultando Gemini para obtener resultado", [
            'home_team' => $homeTeam,
            'away_team' => $awayTeam,
            'date' => $date,
            'league' => $league
        ]);

        try {
            $response = $this->callGemini($prompt, true); // true = usar grounding (web search)

            if ($response) {
                // Procesar la respuesta para extraer los goles
                $result = $this->parseMatchResult($response, $homeTeam, $awayTeam);

                if ($result && isset($result['home_score']) && isset($result['away_score'])) {
                    $ttl = config('gemini.cache.results_ttl', 48 * 60);
                    Cache::put($cacheKey, $result, now()->addMinutes($ttl));

                    Log::info("Resultado obtenido de Gemini", [
                        'home_team' => $homeTeam,
                        'away_team' => $awayTeam,
                        'score' => "{$result['home_score']} - {$result['away_score']}"
                    ]);

                    return $result;
                }
            }
        } catch (\Exception $e) {
            Log::error("Error al consultar Gemini para resultado del partido", [
                'error' => $e->getMessage(),
                'home_team' => $homeTeam,
                'away_team' => $awayTeam
            ]);
        }

        return null;
    }

    /**
     * 🆕 Obtener datos DETALLADOS del partido desde Gemini
     * Extrae: score, eventos (goles, tarjetas, autogoles, penales), etc.
     * Permite verificar preguntas basadas en eventos
     */
    public function getDetailedMatchData($homeTeam, $awayTeam, $date, $league = null, $forceRefresh = false)
    {
        $cacheKey = "gemini_detailed_match_" . md5("{$homeTeam}_{$awayTeam}_{$date}");

        if (!$forceRefresh && Cache::has($cacheKey)) {
            Log::info("Datos detallados en caché para {$homeTeam} vs {$awayTeam}");
            return Cache::get($cacheKey);
        }

        // Construir prompt para obtener datos detallados
        $dateFormatted = Carbon::parse($date)->format('d de F de Y');
        $leagueInfo = $league ? " en {$league}" : "";

        $prompt = <<<EOT
Busca información DETALLADA del partido entre {$homeTeam} y {$awayTeam} que se jugó el {$dateFormatted}{$leagueInfo}.

Proporciona la información en formato JSON con esta estructura exacta:
{
    "final_score": "3-0",
    "home_goals": 3,
    "away_goals": 0,
    "first_goal_scorer": "nombre_jugador",
    "first_goal_team": "HOME",
    "last_goal_scorer": "nombre_jugador", 
    "last_goal_team": "HOME",
    "both_teams_scored": false,
    "events": [
        {"minute": "15", "type": "GOAL", "team": "HOME", "player": "Jugador 1"},
        {"minute": "35", "type": "YELLOW_CARD", "team": "AWAY", "player": "Jugador 2"},
        {"minute": "45", "type": "RED_CARD", "team": "HOME", "player": "Jugador 3"},
        {"minute": "60", "type": "OWN_GOAL", "team": "AWAY", "player": "Jugador 4"},
        {"minute": "70", "type": "PENALTY_GOAL", "team": "HOME", "player": "Jugador 5"}
    ],
    "home_possession": 55,
    "away_possession": 45,
    "home_fouls": 12,
    "away_fouls": 14,
    "total_goals": 3,
    "total_yellow_cards": 2,
    "total_red_cards": 1,
    "total_own_goals": 0,
    "total_penalty_goals": 1
}

IMPORTANTE: 
- Devuelve SOLO el JSON, sin explicaciones
- Si el partido no se jugó, devuelve: {"status": "NOT_PLAYED"}
- Si no encuentras información, devuelve: {"status": "NOT_FOUND"}
- Los tipos de evento válidos: GOAL, YELLOW_CARD, RED_CARD, OWN_GOAL, PENALTY_GOAL
- Los teams válidos: HOME, AWAY
EOT;

        Log::info("Consultando Gemini para datos detallados del partido", [
            'home_team' => $homeTeam,
            'away_team' => $awayTeam,
            'date' => $date
        ]);

        try {
            $response = $this->callGemini($prompt, true); // true = usar grounding (web search)

            if ($response) {
                $matchData = $this->parseDetailedMatchData($response, $homeTeam, $awayTeam);

                if ($matchData && isset($matchData['home_goals']) && isset($matchData['away_goals'])) {
                    $ttl = config('gemini.cache.results_ttl', 48 * 60);
                    Cache::put($cacheKey, $matchData, now()->addMinutes($ttl));

                    Log::info("✅ Datos detallados del partido obtenidos de Gemini", [
                        'home_team' => $homeTeam,
                        'away_team' => $awayTeam,
                        'score' => "{$matchData['home_goals']} - {$matchData['away_goals']}",
                        'events_count' => count($matchData['events'] ?? [])
                    ]);

                    return $matchData;
                }
            }
        } catch (\Exception $e) {
            Log::error("Error al consultar Gemini para datos detallados", [
                'error' => $e->getMessage(),
                'home_team' => $homeTeam,
                'away_team' => $awayTeam
            ]);
        }

        return null;
    }

    /**
     * Parsear datos detallados del partido desde respuesta de Gemini
     */
    protected function parseDetailedMatchData($response, $homeTeam, $awayTeam)
    {
        try {
            // Si es un array (Gemini lo retorna así a veces), convertir a string
            if (is_array($response)) {
                $response = json_encode($response);
            }

            $responseStr = (string)$response;

            // Limpiar la respuesta (remover markdown code blocks si existen)
            $responseStr = preg_replace('/```json\s*/', '', $responseStr);
            $responseStr = preg_replace('/```\s*/', '', $responseStr);

            // Intentar parsear como JSON
            $data = json_decode($responseStr, true);

            if (!$data || !is_array($data)) {
                Log::warning("No se pudo parsear datos detallados como JSON", [
                    'response_preview' => substr($responseStr, 0, 500)
                ]);
                return null;
            }

            // Validar que tenemos los campos mínimos requeridos
            if (!isset($data['home_goals']) || !isset($data['away_goals'])) {
                Log::warning("Datos JSON incompletos - faltan home_goals o away_goals", [
                    'data' => $data
                ]);
                return null;
            }

            // Limpiar y validar datos
            $cleanData = [
                'home_goals' => (int)($data['home_goals'] ?? 0),
                'away_goals' => (int)($data['away_goals'] ?? 0),
                'first_goal_scorer' => $data['first_goal_scorer'] ?? null,
                'first_goal_team' => $data['first_goal_team'] ?? null,
                'last_goal_scorer' => $data['last_goal_scorer'] ?? null,
                'last_goal_team' => $data['last_goal_team'] ?? null,
                'both_teams_scored' => (bool)($data['both_teams_scored'] ?? false),
                'home_possession' => (int)($data['home_possession'] ?? 0),
                'away_possession' => (int)($data['away_possession'] ?? 0),
                'home_fouls' => (int)($data['home_fouls'] ?? 0),
                'away_fouls' => (int)($data['away_fouls'] ?? 0),
                'total_yellow_cards' => (int)($data['total_yellow_cards'] ?? 0),
                'total_red_cards' => (int)($data['total_red_cards'] ?? 0),
                'total_own_goals' => (int)($data['total_own_goals'] ?? 0),
                'total_penalty_goals' => (int)($data['total_penalty_goals'] ?? 0),
                'events' => is_array($data['events'] ?? null) ? $data['events'] : []
            ];

            return $cleanData;
        } catch (\Exception $e) {
            Log::error("Error al parsear datos detallados del partido", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Parsear respuesta de Gemini para extraer marcador
     */
    protected function parseMatchResult($response, $homeTeam, $awayTeam)
    {
        // Si es un array, convertir a string (Gemini puede retornar array de candidates)
        if (is_array($response)) {
            $response = json_encode($response);
        }

        // Asegurar que es string
        $responseStr = (string)$response;

        // Buscar patrones como "3 - 2" o "3-2" en la respuesta
        if (preg_match('/(\d+)\s*-\s*(\d+)/', $responseStr, $matches)) {
            return [
                'home_score' => (int)$matches[1],
                'away_score' => (int)$matches[2],
                'raw_response' => substr($responseStr, 0, 200) // Guardar primeros 200 chars
            ];
        }

        // Si la respuesta dice que no fue jugado o no se encontró
        if (stripos($responseStr, 'NO_JUGADO') !== false || stripos($responseStr, 'no se ha jugado') !== false) {
            Log::warning("Partido no ha sido jugado según Gemini", [
                'home_team' => $homeTeam,
                'away_team' => $awayTeam
            ]);
            return null;
        }

        if (stripos($responseStr, 'NO_ENCONTRADO') !== false) {
            Log::warning("No se encontró información del partido en Gemini", [
                'home_team' => $homeTeam,
                'away_team' => $awayTeam
            ]);
            return null;
        }

        return null;
    }

    /**
     * Llamada principal a la API de Gemini con retry logic
     */
    public function callGemini($userMessage, $useGrounding = false, $attempt = 1)
    {
        try {
            Log::debug("Llamada a Gemini - Intento {$attempt}/{$this->maxRetries}");

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $userMessage
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.5,
                    'maxOutputTokens' => 4096,
                ]
            ];

            // Implementar grounding (web search) si está habilitado
            // Solo disponible en algunos modelos (gemini-2.5-flash, gemini-pro, etc.)
            if ($useGrounding && $this->groundingEnabled) {
                $payload['tools'] = [
                    [
                        'googleSearch' => new \stdClass() // Habilitar búsqueda web
                    ]
                ];
                Log::debug("Grounding (web search) habilitado para esta llamada");
            }

            if (config('gemini.logging.log_requests')) {
                Log::debug("Payload enviado a Gemini", ['payload' => $payload]);
            }

            $response = Http::timeout($this->timeout)->post(
                "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}",
                $payload
            );

            if ($response->failed()) {
                if ($response->status() === 429) { // Rate limited
                    $wait_time = 90 * $attempt; // 90s, 180s, 270s, etc. (más tolerante)
                    Log::warning("Rate limited por Gemini (429), intento {$attempt}/{$this->maxRetries}, esperando {$wait_time}s...");
                    if ($attempt < $this->maxRetries) {
                        echo "\n⏳ Rate limitado por Gemini. Esperando {$wait_time} segundos (intento {$attempt}/{$this->maxRetries})...\n";
                        sleep($wait_time);
                        return $this->callGemini($userMessage, $useGrounding, $attempt + 1);
                    }
                    throw new Exception("Rate limited por API de Gemini - máximo de reintentos alcanzado (después de " . ($this->maxRetries * 90) . " segundos total)");
                }

                throw new Exception("Gemini API error (HTTP " . $response->status() . "): " . substr($response->body(), 0, 200));
            }

            $data = $response->json();

            if (config('gemini.logging.log_responses')) {
                Log::debug("Respuesta de Gemini", ['response' => $data]);
            }

            // Extraer el contenido de la respuesta
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $content = $data['candidates'][0]['content']['parts'][0]['text'];

                // Limpiar markdown JSON si está presente
                $content = trim($content);
                if (str_starts_with($content, '```json')) {
                    $content = substr($content, 7); // remover ```json
                }
                if (str_starts_with($content, '```')) {
                    $content = substr($content, 3); // remover ```
                }
                if (str_ends_with($content, '```')) {
                    $content = substr($content, 0, -3); // remover ```
                }
                $content = trim($content);

                // Limpiar caracteres de control que causan problemas en JSON
                // Preservar saltos de línea (\n) pero remover otros caracteres de control
                $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $content);

                // Intentar parsear como JSON
                $parsed = json_decode($content, true);
                if ($parsed) {
                    return $parsed;
                }

                return ['content' => $content];
            }

            return null;

        } catch (Exception $e) {
            Log::error("Error en llamada a Gemini: " . $e->getMessage());

            // Retry logic
            if ($attempt < $this->maxRetries) {
                sleep($this->retryDelay);
                return $this->callGemini($userMessage, $useGrounding, $attempt + 1);
            }

            throw $e;
        }
    }

    /**
     * Construir prompt para obtener fixtures
     */
    protected function buildFixturesPrompt($league)
    {
        $today = \Carbon\Carbon::now();
        $template = config('gemini.prompts.fixtures.template');

        return str_replace(
            ['{league}', '{current_date}', '{next_7_days}'],
            [
                $league,
                $today->format('d de F de Y'),
                $today->copy()->addDays(7)->format('d de F de Y')
            ],
            $template
        );
    }

    /**
     * Construir prompt para obtener resultados
     */
    protected function buildResultsPrompt($league, $date)
    {
        $template = config('gemini.prompts.results.template');
        return str_replace(
            ['{league}', '{date}'],
            [$league, $date],
            $template
        );
    }

    /**
     * Construir prompt para análisis de partido
     */
    protected function buildAnalysisPrompt($homeTeam, $awayTeam, $date)
    {
        $template = config('gemini.prompts.analysis.template');
        return str_replace(
            ['{home_team}', '{away_team}', '{date}'],
            [$homeTeam, $awayTeam, $date],
            $template
        );
    }

    /**
     * Obtener información del sistema para prompts
     */
    public function getSystemPrompt($type = 'fixtures')
    {
        return config("gemini.prompts.{$type}.system", '');
    }

    /**
     * Limpiar caché de Gemini
     */
    public function clearCache($type = null)
    {
        if ($type) {
            Cache::forget("gemini_{$type}");
            Log::info("Caché de Gemini tipo {$type} limpiado");
        } else {
            Cache::flush();
            Log::info("Toda la caché de Gemini limpiada");
        }
    }
}
