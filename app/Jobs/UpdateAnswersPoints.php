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
            $correctOption = collect($this->templateQuestion->options)
                ->first(function ($option) {
                    return $option['is_correct'] ?? false;
                });

            if (!$correctOption) {
                Log::warning('No se encontró una opción correcta para la pregunta', [
                    'template_question_id' => $this->templateQuestion->id
                ]);
                return;
            }

            $questions = Question::where('template_question_id', $this->templateQuestion->id)
                ->with(['answers.questionOption'])
                ->get();

            foreach ($questions as $question) {
                foreach ($question->answers as $answer) {
                    if ($answer->questionOption && $answer->questionOption->text === $correctOption['text']) {
                        $answer->update([
                            'is_correct' => true,
                            'points_earned' => $question->points,
                        ]);
                    } else {
                        $answer->update([
                            'is_correct' => false,
                            'points_earned' => 0,
                        ]);
                    }
                }
            }

            Log::info('Puntos actualizados exitosamente', [
                'template_question_id' => $this->templateQuestion->id,
            ]);

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
