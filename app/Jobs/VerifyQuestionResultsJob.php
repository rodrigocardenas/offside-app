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
                foreach ($correctAnswers as $correctAnswerText) {
                    $option = $question->options->first(function($option) use ($correctAnswerText) {
                        return stripos($option->text, $correctAnswerText) !== false ||
                               stripos($correctAnswerText, $option->text) !== false;
                    });
                    if ($option) {
                        $correctOptionIds[] = $option->id;
                    }
                }

                Log::info('Verificación completada', [
                    'question_id' => $question->id,
                    'correct_answers_text' => $correctAnswers->toArray(),
                    'correct_option_ids' => $correctOptionIds
                ]);

                // Actualizar las respuestas correctas
                foreach ($answers as $answer) {
                    $answer->is_correct = in_array($answer->option_id, $correctOptionIds);
                    $answer->points_earned = $answer->is_correct ? 300 : 0;
                    $answer->save();
                }

                // Marcar la pregunta como verificada
                $question->result_verified_at = now();
                $question->save();

                Log::info('Pregunta verificada correctamente: ' . $question->id, [
                    'match_date' => $match->date,
                    'match_teams' => $match->home_team . ' vs ' . $match->away_team
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
