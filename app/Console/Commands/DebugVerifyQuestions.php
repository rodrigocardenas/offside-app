<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Services\QuestionEvaluationService;
use Illuminate\Console\Command;
use Throwable;

class DebugVerifyQuestions extends Command
{
    protected $signature = 'app:debug-verify-questions {--match-id=297}';
    protected $description = 'Debug question verification process';

    public function handle()
    {
        $matchId = $this->option('match-id');

        $this->line("\n╔════════════════════════════════════════════════════════════╗");
        $this->line("║ Debug Verificación de Preguntas                            ║");
        $this->line("╚════════════════════════════════════════════════════════════╝\n");

        $questions = Question::where('match_id', $matchId)
            ->whereNull('result_verified_at')
            ->with(['football_match', 'options', 'answers'])
            ->get();

        $this->line("Match ID: {$matchId}");
        $this->line("Preguntas pendientes: {$questions->count()}");

        if ($questions->isEmpty()) {
            $this->warn('No hay preguntas pendientes');
            return;
        }

        $evaluationService = app('App\Services\QuestionEvaluationService');

        foreach ($questions->take(3) as $question) {
            $this->line("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->line("Pregunta ID: {$question->id}");
            $this->line("Título: {$question->title}");
            $this->line("Puntos: {$question->points}");
            $this->line("Respuestas: {$question->answers->count()}");
            $this->line("Opciones: {$question->options->count()}");

            try {
                if (!$question->football_match) {
                    $this->error("✗ Error: La pregunta no tiene partido asignado correctamente");
                    continue;
                }

                $this->line("🔄 Evaluando...");
                $correctOptionIds = $evaluationService->evaluateQuestion($question, $question->football_match);

                $this->line("✓ Opciones correctas: " . implode(', ', $correctOptionIds));

                // Update options
                foreach ($question->options as $option) {
                    $option->is_correct = in_array($option->id, $correctOptionIds);
                    $option->save();
                }

                // Update answers and assign points
                $pointsAssigned = 0;
                foreach ($question->answers as $answer) {
                    $isCorrect = in_array($answer->question_option_id, $correctOptionIds);
                    $answer->is_correct = $isCorrect;
                    $answer->points_earned = $isCorrect ? ($question->points ?? 300) : 0;
                    $answer->save();

                    if ($isCorrect) {
                        $pointsAssigned += $answer->points_earned;
                    }
                }

                $question->result_verified_at = now();
                $question->save();

                $this->line("✓ Pregunta verificada");
                $this->line("  - Respuestas correctas: " . $question->answers->where('is_correct', true)->count());
                $this->line("  - Puntos asignados: {$pointsAssigned}");
            } catch (Throwable $e) {
                $this->error("✗ Error: {$e->getMessage()}");
            }
        }

        $this->line("\n╔════════════════════════════════════════════════════════════╗");
        $this->line("║ Debug completado                                           ║");
        $this->line("╚════════════════════════════════════════════════════════════╝\n");
    }
}
