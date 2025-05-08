<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
                ['role' => 'system', 'content' => 'Actúa como un verificador de resultados de fútbol. Tu trabajo es determinar las respuestas correctas a preguntas sobre un partido basándote en la información del resultado.'],
                ['role' => 'user', 'content' => "Para el partido {$match['homeTeam']} vs {$match['awayTeam']} (Resultado: {$match['score']}, Eventos: {$match['events']}), verifica las respuestas correctas para estas preguntas:\n{$questionsText}"],
            ],
        ]);

        $content = $response->choices[0]->message->content;
        $results = json_decode($content, true);

        return collect($results['respuestas'] ?? []);
    }
}
