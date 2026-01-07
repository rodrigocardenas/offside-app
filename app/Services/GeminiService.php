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
        $this->apiKey = config('gemini.api_key');
        $this->model = config('gemini.model', 'gemini-3-pro-preview');
        $this->maxRetries = config('gemini.max_retries', 5);
        $this->retryDelay = config('gemini.retry_delay', 2);
        $this->groundingEnabled = config('gemini.grounding_enabled', true);
        $this->timeout = config('gemini.timeout', 60);

        if (!$this->apiKey) {
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
                    $wait_time = 60 * $attempt; // 60s, 120s, 180s, etc.
                    Log::warning("Rate limited por Gemini (429), intento {$attempt}/{$this->maxRetries}, esperando {$wait_time}s...");
                    if ($attempt < $this->maxRetries) {
                        sleep($wait_time);
                        return $this->callGemini($userMessage, $useGrounding, $attempt + 1);
                    }
                    throw new Exception("Rate limited por API de Gemini - máximo de reintentos alcanzado");
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
