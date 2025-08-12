<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Question;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\Log;

class VerifyQuestionResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos
    public $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(OpenAIService $openAIService)
    {
        Log::info('Iniciando verificación de resultados de preguntas');

        // Verificar resultados de preguntas de partidos finalizados
        $pendingQuestions = Question::whereNull('result_verified_at')
            ->whereHas('football_match', function($query) {
                $query->whereIn('status', ['FINISHED', 'Match Finished']);
            })
            ->get();

        Log::info('Preguntas pendientes de verificación encontradas: ' . $pendingQuestions->count());

        foreach ($pendingQuestions as $question) {
            try {
                $match = $question->football_match;
                $answers = $question->answers;

                // Verificar resultados usando OpenAI
                $correctAnswers = $openAIService->verifyMatchResults(
                    [
                        'homeTeam' => $match->home_team,
                        'awayTeam' => $match->away_team,
                        'score' => $match->score,
                        'events' => $match->events
                    ],
                    [
                        [
                            'title' => $question->title,
                            'options' => $question->options->pluck('text')->toArray()
                        ]
                    ]
                );

                // Convertir las respuestas correctas de texto a IDs de opciones
                $correctOptionIds = [];
                $correctOptions = [];

                Log::info('Respuesta de OpenAI:', [
                    'question_id' => $question->id,
                    'openai_response' => $correctAnswers->toArray(),
                    'available_options' => $question->options->pluck('text')->toArray()
                ]);

                foreach ($correctAnswers as $correctAnswerText) {
                    // Buscar coincidencias exactas primero
                    $exactMatch = $question->options->first(function($option) use ($correctAnswerText) {
                        return strtolower(trim($option->text)) === strtolower(trim($correctAnswerText));
                    });

                    if ($exactMatch) {
                        $correctOptionIds[] = $exactMatch->id;
                        $correctOptions[] = $exactMatch->text;
                        Log::info("Coincidencia exacta encontrada: '{$exactMatch->text}'");
                        continue;
                    }

                    // Si no hay coincidencia exacta, buscar coincidencias parciales
                    $partialMatch = $question->options->first(function($option) use ($correctAnswerText) {
                        return stripos(trim($option->text), trim($correctAnswerText)) !== false ||
                               stripos(trim($correctAnswerText), trim($option->text)) !== false;
                    });

                    if ($partialMatch) {
                        $correctOptionIds[] = $partialMatch->id;
                        $correctOptions[] = $partialMatch->text;
                        Log::info("Coincidencia parcial encontrada: '{$partialMatch->text}' para '{$correctAnswerText}'");
                    } else {
                        Log::warning("No se encontró coincidencia para: '{$correctAnswerText}'");
                    }
                }

                Log::info('Verificación completada', [
                    'question_id' => $question->id,
                    'correct_answers_text' => $correctAnswers->toArray(),
                    'correct_options_found' => $correctOptions,
                    'correct_option_ids' => $correctOptionIds
                ]);

                // Actualizar las opciones correctas en question_options
                foreach ($question->options as $option) {
                    $wasCorrect = $option->is_correct;
                    $option->is_correct = in_array($option->id, $correctOptionIds);
                    $option->save();

                    if ($wasCorrect !== $option->is_correct) {
                        Log::info("Opción actualizada: '{$option->text}' - is_correct: " . ($option->is_correct ? 'true' : 'false'));
                    }
                }

                // Actualizar las respuestas correctas en answers
                $updatedAnswers = 0;
                foreach ($answers as $answer) {
                    $wasCorrect = $answer->is_correct;
                    $answer->is_correct = in_array($answer->question_option_id, $correctOptionIds);
                    $answer->points_earned = $answer->is_correct ? 300 : 0;
                    $answer->save();

                    if ($wasCorrect !== $answer->is_correct) {
                        $updatedAnswers++;
                    }
                }

                // Marcar la pregunta como verificada
                $question->result_verified_at = now();
                $question->save();

                Log::info('Pregunta verificada correctamente: ' . $question->id, [
                    'match_date' => $match->date,
                    'match_teams' => $match->home_team . ' vs ' . $match->away_team,
                    'correct_options' => $correctOptions,
                    'answers_updated' => $updatedAnswers,
                    'total_answers' => $answers->count()
                ]);

            } catch (\Exception $e) {
                Log::error('Error al verificar resultados para la pregunta ' . $question->id, [
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        Log::info('Finalizada verificación de resultados de preguntas');
    }
}
