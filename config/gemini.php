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
            'system' => 'Eres un experto en fútbol. Proporciona información sobre el calendario (fixtures) de fútbol. Responde SIEMPRE en JSON válido.',
            'template' => 'Busca y proporciona el calendario de partidos para la liga {league} de los próximos 7 días. '
                . 'Responde ÚNICAMENTE con un JSON con esta estructura exacta (SIN texto adicional): '
                . '{"matches": [{"home_team": "string", "away_team": "string", "date": "YYYY-MM-DD HH:mm", "status": "scheduled|live|finished", "stadium": "string o null", "stage": "string o null"}]} '
                . 'Si no hay datos suficientes, retorna fixtures ficticios o aproximados basados en calendarios conocidos de 2024-2025.',
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
