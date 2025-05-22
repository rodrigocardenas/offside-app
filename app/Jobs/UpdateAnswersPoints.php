<?php

namespace App\Jobs;

use App\Models\TemplateQuestion;
use App\Models\Question;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateAnswersPoints implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $templateQuestion;

    /**
     * Create a new job instance.
     */
    public function __construct(TemplateQuestion $templateQuestion)
    {
        $this->templateQuestion = $templateQuestion;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Obtener todas las preguntas basadas en esta plantilla
            $questions = Question::where('template_question_id', $this->templateQuestion->id)->get();

            foreach ($questions as $question) {
                try {
                    // Obtener la opción correcta de la plantilla
                    $correctOption = collect($this->templateQuestion->options)
                        ->firstWhere('is_correct', true);

                    if (!$correctOption) {
                        Log::warning('No se encontró opción correcta para la plantilla', [
                            'template_question_id' => $this->templateQuestion->id,
                            'question_id' => $question->id
                        ]);
                        continue;
                    }

                    // Calcular los puntos base
                    $basePoints = 300;
                    $points = $this->templateQuestion->is_featured ? $basePoints * 2 : $basePoints;

                    // Actualizar los puntos de las respuestas correctas
                    DB::transaction(function () use ($question, $correctOption, $points) {
                        // Actualizar respuestas correctas
                        $question->answers()
                            ->where('category', 'predictive')
                            ->update(['points_earned' => $points]);

                        // Actualizar puntos de los usuarios en la tabla pivote
                        $question->answers()
                            ->where('category', 'predictive')
                            ->each(function ($answer) use ($points, $question) {
                                try {
                                    // Actualizar puntos totales del usuario
                                    $answer->user()->increment('points', $points);

                                    // Actualizar puntos del usuario en el grupo
                                    DB::table('group_user')
                                        ->where('group_id', $question->group_id)
                                        ->where('user_id', $answer->user_id)
                                        ->increment('points', $points);
                                } catch (\Exception $e) {
                                    Log::error('Error al actualizar puntos del usuario', [
                                        'error' => $e->getMessage(),
                                        'user_id' => $answer->user_id,
                                        'group_id' => $question->group_id,
                                        'points' => $points
                                    ]);
                                }
                            });
                    });

                    Log::info('Puntos actualizados exitosamente', [
                        'question_id' => $question->id,
                        'template_question_id' => $this->templateQuestion->id,
                        'points' => $points
                    ]);

                } catch (\Exception $e) {
                    Log::error('Error al procesar pregunta', [
                        'error' => $e->getMessage(),
                        'question_id' => $question->id,
                        'template_question_id' => $this->templateQuestion->id
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error general en UpdateAnswersPoints', [
                'error' => $e->getMessage(),
                'template_question_id' => $this->templateQuestion->id,
                'trace' => $e->getTraceAsString()
            ]);

            // Relanzar la excepción para que Laravel la maneje
            throw $e;
        }
    }
}
