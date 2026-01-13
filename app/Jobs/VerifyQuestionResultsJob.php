<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Question;
use App\Services\QuestionEvaluationService;
use Illuminate\Support\Facades\Log;

class VerifyQuestionResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos
    public $tries = 3;

    /**
     * Execute the job.
     *
     * Evalúa preguntas de partidos finalizados usando lógica determinística
     * en lugar de OpenAI, asegurando resultados consistentes y predecibles.
     * 
     * OPTIMIZACIÓN: Utiliza chunking para procesar de 50 en 50 y evitar
     * cargar todas las preguntas en memoria.
     */
    public function handle(QuestionEvaluationService $evaluationService)
    {
        Log::info('Iniciando verificación de resultados de preguntas (determinística - chunking)');

        $chunkSize = 50; // Procesar de 50 en 50 para evitar problemas de memoria
        $processedCount = 0;
        $errorCount = 0;

        // Usar chunking para procesar de manera más eficiente
        Question::whereNull('result_verified_at')
            ->whereHas('football_match', function($query) {
                $query->whereIn('status', ['FINISHED', 'Match Finished']);
            })
            ->with('football_match', 'options', 'answers')
            ->chunk($chunkSize, function ($questions) use ($evaluationService, &$processedCount, &$errorCount) {
                
                Log::info('Procesando chunk de ' . $questions->count() . ' preguntas');

                foreach ($questions as $question) {
                    try {
                        $match = $question->football_match;

                        if (!$match || !in_array($match->status, ['FINISHED', 'Match Finished'])) {
                            Log::warning('Match not ready for evaluation', [
                                'question_id' => $question->id,
                                'match_id' => $match?->id,
                                'match_status' => $match?->status
                            ]);
                            continue;
                        }

                        // Evaluar pregunta usando lógica determinística
                        $correctOptionIds = $evaluationService->evaluateQuestion($question, $match);

                        if (empty($correctOptionIds)) {
                            Log::warning('No correct options found for question', [
                                'question_id' => $question->id,
                                'question_title' => $question->title,
                                'match_id' => $match->id
                            ]);
                        }

                        // Actualizar opciones correctas
                        foreach ($question->options as $option) {
                            $wasCorrect = $option->is_correct;
                            $option->is_correct = in_array($option->id, $correctOptionIds);
                            $option->save();

                            if ($wasCorrect !== $option->is_correct) {
                                Log::info("Opción actualizada", [
                                    'question_id' => $question->id,
                                    'option_id' => $option->id,
                                    'option_text' => $option->text,
                                    'is_correct' => $option->is_correct
                                ]);
                            }
                        }

                        // Actualizar respuestas de usuarios
                        $updatedAnswers = 0;
                        foreach ($question->answers as $answer) {
                            $wasCorrect = $answer->is_correct;
                            $answer->is_correct = in_array($answer->question_option_id, $correctOptionIds);
                            $answer->points_earned = $answer->is_correct ? $question->points ?? 300 : 0;
                            $answer->save();

                            if ($wasCorrect !== $answer->is_correct) {
                                $updatedAnswers++;
                            }
                        }

                        // Marcar pregunta como verificada
                        $question->result_verified_at = now();
                        $question->save();

                        $processedCount++;

                        Log::info('Pregunta verificada correctamente', [
                            'question_id' => $question->id,
                            'question_type' => $question->type,
                            'question_title' => $question->title,
                            'match' => $match->home_team . ' vs ' . $match->away_team,
                            'correct_options_count' => count($correctOptionIds),
                            'answers_updated' => $updatedAnswers,
                            'total_answers' => $question->answers->count()
                        ]);

                    } catch (\Exception $e) {
                        $errorCount++;
                        Log::error('Error al verificar resultados para la pregunta', [
                            'question_id' => $question->id,
                            'error' => $e->getMessage(),
                            'exception' => get_class($e)
                        ]);
                        continue;
                    }
                }
            });

        Log::info('Finalizada verificación de resultados de preguntas', [
            'total_processed' => $processedCount,
            'total_errors' => $errorCount
        ]);
    }
}
