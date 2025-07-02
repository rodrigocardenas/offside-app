<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Question;
use App\Models\Answer;
use App\Models\FootballMatch;
use App\Services\OpenAIService;

class TestVerification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:verification {question_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the verification process for a specific question';

    /**
     * Execute the console command.
     */
    public function handle(OpenAIService $openAIService)
    {
        $questionId = $this->argument('question_id');

        $question = Question::with(['answers.user', 'answers.questionOption', 'options', 'football_match'])->find($questionId);

        if (!$question) {
            $this->error("Pregunta con ID $questionId no encontrada");
            return;
        }

        $this->info("=== PRUEBA DE VERIFICACIÓN ===\n");
        $this->info("Pregunta ID: {$question->id}");
        $this->info("Título: {$question->title}");
        $this->info("Tipo: {$question->type}");

        if ($question->football_match) {
            $match = $question->football_match;
            $this->info("Partido: {$match->home_team} vs {$match->away_team}");
            $this->info("Score: {$match->score}");
            $this->info("Estado: {$match->status}");
        }

        $this->info("Respuestas antes de verificación: {$question->answers->count()}");

        foreach ($question->answers as $answer) {
            $userName = $answer->user ? $answer->user->name : 'Usuario desconocido';
            $optionText = $answer->questionOption ? $answer->questionOption->text : 'Opción desconocida';
            $isCorrect = $answer->is_correct ? '✅' : '❌';
            $points = $answer->points_earned ?? 0;

            $this->info("  - {$userName}: {$optionText} {$isCorrect} ({$points} puntos)");
        }

        // Simular el proceso de verificación
        if ($question->football_match && $question->football_match->status === 'Match Finished') {
            $this->info("\n=== SIMULANDO VERIFICACIÓN ===");

            try {
                $match = $question->football_match;

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

                $this->info("Respuestas correctas según OpenAI: " . implode(', ', $correctAnswers->toArray()));

                                // Actualizar las respuestas correctas
                $updatedCount = 0;
                foreach ($question->answers as $answer) {
                    $wasCorrect = $answer->is_correct;
                    $oldPoints = $answer->points_earned;
                    if ($oldPoints === null) {
                        $oldPoints = 0;
                    }

                    $answer->is_correct = in_array($answer->option_id, $correctAnswers->toArray());
                    $answer->points_earned = $answer->is_correct ? 300 : 0;
                    $answer->save();

                    if ($wasCorrect != $answer->is_correct || $oldPoints != $answer->points_earned) {
                        $updatedCount++;
                        $userName = $answer->user ? $answer->user->name : 'Usuario desconocido';
                        $this->info("  Actualizado: {$userName} - " . ($answer->is_correct ? '✅' : '❌') . " ({$answer->points_earned} puntos)");
                    }
                }

                $this->info("Respuestas actualizadas: {$updatedCount}");

                // Marcar la pregunta como verificada
                $question->result_verified_at = now();
                $question->save();

                $this->info("✅ Pregunta marcada como verificada");

            } catch (\Exception $e) {
                $this->error("❌ Error en verificación: " . $e->getMessage());
            }
        } else {
            $this->warn("El partido no está finalizado o no hay partido asociado");
        }
    }
}
