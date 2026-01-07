<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Gemini API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para la integración con Google Gemini API
    | con capacidades de búsqueda en internet (grounding)
    |
    */

    'api_key' => env('GEMINI_API_KEY'),

    'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),

    'grounding_enabled' => env('GEMINI_GROUNDING_ENABLED', true),

    'timeout' => 30,

    'max_retries' => 3,

    'retry_delay' => 2, // segundos

    /*
    |--------------------------------------------------------------------------
    | Caché Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de caché para resultados de Gemini
    |
    */
    'cache' => [
        'enabled' => true,
        'fixtures_ttl' => 24 * 60, // 24 horas en minutos
        'results_ttl' => 48 * 60,  // 48 horas en minutos
        'analysis_ttl' => 72 * 60, // 72 horas en minutos
    ],

    /*
    |--------------------------------------------------------------------------
    | Límites de Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Limita las llamadas a la API de Gemini para evitar exceder cuotas
    |
    */
    'rate_limiting' => [
        'enabled' => true,
        'max_calls_per_minute' => 10,
        'max_calls_per_hour' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Prompts Configuration
    |--------------------------------------------------------------------------
    |
    | Plantillas de prompts para diferentes tipos de búsquedas
    |
    */
    'prompts' => [
        'fixtures' => [
            'system' => 'Eres un experto en fútbol. Responde SIEMPRE únicamente en JSON válido sin texto adicional.',
            'template' => 'Hoy es {current_date}. Proporciona el calendario de partidos de {league} para los próximos 7 días (desde hoy hasta {next_7_days}). SOLO partidos con fechas en 2026. Responde ÚNICAMENTE con: '
                . '{"matches":[{"home_team":"X","away_team":"Y","date":"YYYY-MM-DD HH:mm","status":"scheduled","stadium":"Z"}]}',
        ],
        'results' => [
            'system' => 'Eres un experto en análisis de fútbol. Proporciona resultados y estadísticas de partidos. Responde SIEMPRE en JSON válido.',
            'template' => 'Busca y proporciona los resultados de partidos de {league} jugados el {date}. '
                . 'Responde ÚNICAMENTE con un JSON con esta estructura exacta (SIN texto adicional): '
                . '{"matches": [{"home_team": "string", "away_team": "string", "home_score": "número", "away_score": "número", "date": "YYYY-MM-DD HH:mm", "status": "finished"}]} '
                . 'Si no hay datos exactos, usa información de eventos similares conocidos.',
        ],
        'analysis' => [
            'system' => 'Eres un analista de fútbol profesional. Proporciona análisis profundo de partidos. Responde SIEMPRE en JSON válido.',
            'template' => 'Analiza el partido entre {home_team} vs {away_team} jugado el {date}. '
                . 'Responde ÚNICAMENTE con un JSON con análisis detallado. '
                . 'Incluye: resumen, análisis táctico, jugadores clave, momentos decisivos, estadísticas principales.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configuración de logging para debug
    |
    */
    'logging' => [
        'enabled' => true,
        'channel' => 'stack',
        'log_requests' => env('APP_DEBUG', false),
        'log_responses' => env('APP_DEBUG', false),
    ],
];
