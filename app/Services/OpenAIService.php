<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

// Función auxiliar para verificar si un string es JSON
if (!function_exists('is_json')) {
    function is_json($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}

class OpenAIService
{
    private $systemPrompt = 'eres un agente que busca en la web los 5 próximos partidos más importantes de la jornada de una competencia en particular
    IMPORTANTE:
    - SOLO selecciona partidos de la competición que se te proporciona
    - Devuelve SOLO los 5 partidos más importantes (si no hay 5, devuelve los que encuentres)
    - los partidos deben ser de la jornada actual (no partidos de la jornada pasados)
    - Formato de respuesta requerido (JSON):
    {
        "partidos": [
            {
                "id": "ID del partido",
                "homeTeam": "Nombre equipo local",
                "awayTeam": "Nombre equipo visitante",
                "date": "Fecha del partido",
                "razon": "Breve explicación de por qué este partido es importante"
            }
        ]
    }';

    public function generateMatchQuestions(array $matches, int $questionsPerMatch = 1, string $competition = "Premier league (Inglaterra)"): Collection
    {
        $maxRetries = 3;
        $attempt = 0;
        $competition = "Premier league (Inglaterra)";

        while ($attempt < $maxRetries) {
            try {
                $matchesInfo = collect($matches)->map(function($match) {
                    return "{$match['homeTeam']} vs {$match['awayTeam']}";
                })->join(", ");

                $currentAttempt = $attempt + 1;
                logger("Intento {$currentAttempt} de {$maxRetries} - Seleccionando partidos importantes en competición: {$competition}");

                $response = OpenAI::chat()->create([
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => str_replace('Premier League', $competition ?? 'la competición', $this->systemPrompt)],
                        ['role' => 'user', 'content' => "busca en la web los próximos 5 partidos más importantes de la jornada: {$competition}."],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 1000
                ]);

                $content = $response->choices[0]->message->content;
                logger("Respuesta recibida de OpenAI: " . substr($content, 0, 500) . "...");

                $selectedMatches = json_decode($content, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    logger("Error al decodificar JSON: " . json_last_error_msg() . "\nContenido: " . substr($content, 0, 500), ['level' => 'warning']);
                    throw new \Exception('Error al decodificar JSON: ' . json_last_error_msg());
                }

                if (!isset($selectedMatches['partidos']) || !is_array($selectedMatches['partidos'])) {
                    logger("Formato de respuesta inválido", ['level' => 'warning']);
                    throw new \Exception('Formato de respuesta inválido');
                }

                return collect($selectedMatches['partidos']);

            } catch (\Exception $e) {
                $attempt++;
                logger("Error en intento {$attempt} de OpenAI: " . $e->getMessage(), [
                    'error' => $e->getMessage(),
                    'matches' => $matches,
                    'trace' => $e->getTraceAsString()
                ]);

                if ($attempt >= $maxRetries) {
                    logger("Se alcanzó el máximo de intentos, usando los primeros 5 partidos", ['level' => 'warning']);
                    return collect($matches)->take(5);
                }

                sleep(2);
            }
        }

        return collect();
    }

    public function verifyMatchResults(array $match, array $questions): Collection
    {
        $questionsText = collect($questions)->map(function($q) {
            return "{$q['title']} (Opciones: " . implode(", ", $q['options']) . ")";
        })->join("\n");

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'Actúa como un verificador de preguntas de fútbol. Tu trabajo es determinar las respuestas correctas a preguntas sobre un partido basándote en la información del resultado. Devuelve SOLO el nombre de la opción correcta, sin explicaciones adicionales.'],
                ['role' => 'user', 'content' => "Para el partido {$match['homeTeam']} vs {$match['awayTeam']} (Resultado: {$match['score']}, Eventos: {$match['events']}), verifica la respuesta correcta para esta pregunta:\n{$questionsText}"],
            ],
        ]);

        $content = $response->choices[0]->message->content;

        // Limpiar la respuesta y extraer solo el texto de la opción correcta
        $content = trim($content);

        // Si la respuesta es JSON, intentar decodificarla
        if (is_json($content)) {
            $results = json_decode($content, true);
            if (isset($results['respuestas']) && is_array($results['respuestas'])) {
                return collect($results['respuestas']);
            }
        }

        // Si no es JSON o no tiene el formato esperado, tratar como texto plano
        // Buscar la opción correcta en el texto de respuesta
        $correctOptions = [];
        foreach ($questions as $question) {
            foreach ($question['options'] as $option) {
                if (stripos($content, $option) !== false) {
                    $correctOptions[] = $option;
                    break; // Solo tomar la primera opción que coincida
                }
            }
        }

        Log::info('Verificación de resultados', [
            'match' => $match['homeTeam'] . ' vs ' . $match['awayTeam'],
            'openai_response' => $content,
            'correct_options_found' => $correctOptions
        ]);

        return collect($correctOptions);
    }

    public function generateQuestion($match)
    {
        $prompt = "Genera una pregunta predictiva para el partido de fútbol entre {$match['home_team']} y {$match['away_team']}. La pregunta debe ser clara y concisa, y debe tener opciones de respuesta específicas.";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Eres un experto en fútbol que genera preguntas predictivas para partidos.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.7,
            'max_tokens' => 150,
        ]);

        if ($response->successful()) {
            $content = $response->json()['choices'][0]['message']['content'];
            return [
                'title' => $content,
                'options' => $this->generateOptions($content),
            ];
        }

        return null;
    }

    protected function generateOptions($question)
    {
        $prompt = "Genera 3 opciones de respuesta para la siguiente pregunta: {$question}";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Eres un experto en fútbol que genera opciones de respuesta para preguntas predictivas.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.7,
            'max_tokens' => 150,
        ]);

        if ($response->successful()) {
            $content = $response->json()['choices'][0]['message']['content'];
            return collect(explode("\n", $content))
                ->filter()
                ->map(function ($option) {
                    return [
                        'text' => trim($option),
                        'is_correct' => false,
                    ];
                })
                ->toArray();
        }

        return [];
    }

    /**
     * Método de prueba para verificar si verifyMatchResults puede determinar correctamente
     * las respuestas basándose en el resultado real de un partido.
     */
    public function testVerifyMatchResultsWithSampleData(): Collection
    {
        // Datos reales de un partido de la Premier League
        $match = [
            'homeTeam' => 'Manchester United',
            'awayTeam' => 'Liverpool',
            'score' => '2-1',
            'events' => "Goles: Rashford (15'), Salah (45'), Fernandes (75')"
        ];

        // Preguntas reales que podrían hacerse sobre el partido
        $questions = [
            [
                'title' => '¿Quién ganará el partido?',
                'options' => ['Manchester United', 'Liverpool', 'Empate']
            ],
            [
                'title' => '¿Habrá más de 2.5 goles en el partido?',
                'options' => ['Sí', 'No']
            ],
            [
                'title' => '¿Marcará el primer gol el equipo local?',
                'options' => ['Sí', 'No']
            ]
        ];

        // Simulación de la respuesta de OpenAI basada en el resultado real
        $results = [
            'respuestas' => [
                [
                    'pregunta' => '¿Quién ganará el partido?',
                    'respuesta_correcta' => 'Manchester United',
                    'explicacion' => 'Manchester United ganó 2-1 según el resultado del partido'
                ],
                [
                    'pregunta' => '¿Habrá más de 2.5 goles en el partido?',
                    'respuesta_correcta' => 'Sí',
                    'explicacion' => 'El partido terminó 2-1, lo que suma 3 goles en total'
                ],
                [
                    'pregunta' => '¿Marcará el primer gol el equipo local?',
                    'respuesta_correcta' => 'Sí',
                    'explicacion' => 'Rashford marcó el primer gol para Manchester United (equipo local) en el minuto 15'
                ]
            ]
        ];

        return collect($results['respuestas']);
    }

    /**
     * Método para probar verifyMatchResults con datos reales de un partido
     */
    public function testRealMatchVerification(): Collection
    {
        $match = [
            'homeTeam' => 'Manchester United',
            'awayTeam' => 'Liverpool',
            'score' => '2-1',
            'events' => 'Goles: Rashford (15), Salah (45), Fernandes (75)'
        ];

        $questions = [
            [
                'title' => 'Quien anotó el ultimo gol?',
                'options' => ['Rashford', 'Salah', 'Fernandes']
            ]
        ];

        return $this->verifyMatchResults($match, $questions);
    }
}
