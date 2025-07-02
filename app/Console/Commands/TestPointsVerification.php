<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Question;
use App\Models\Answer;
use App\Models\FootballMatch;
use App\Services\OpenAIService;

class TestPointsVerification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:points-verification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test points verification with real data';

    /**
     * Execute the console command.
     */
    public function handle(OpenAIService $openAIService)
    {
        $this->info("=== PRUEBA DE VERIFICACIÓN DE PUNTOS ===\n");

        // Buscar preguntas con respuestas que no han sido verificadas
        $pendingQuestions = Question::whereHas('answers')
            ->whereNull('result_verified_at')
            ->whereHas('football_match', function($query) {
                $query->whereIn('status', ['FINISHED', 'Match Finished']);
            })
            ->with(['answers.user', 'answers.questionOption', 'options', 'football_match'])
            ->get();

        if ($pendingQuestions->isEmpty()) {
            $this->warn("No hay preguntas pendientes de verificación con respuestas.");

            // Mostrar preguntas verificadas recientemente
            $verifiedQuestions = Question::whereHas('answers')
                ->whereNotNull('result_verified_at')
                ->whereHas('football_match', function($query) {
                    $query->whereIn('status', ['FINISHED', 'Match Finished']);
                })
                ->with(['answers.user', 'answers.questionOption', 'options', 'football_match'])
                ->orderBy('result_verified_at', 'desc')
                ->take(3)
                ->get();

            if ($verifiedQuestions->isNotEmpty()) {
                $this->info("Preguntas verificadas recientemente:");
                foreach ($verifiedQuestions as $question) {
                    $this->info("ID: {$question->id} - {$question->title}");
                    $this->info("  Partido: {$question->football_match->home_team} vs {$question->football_match->away_team}");
                    $this->info("  Score: {$question->football_match->score}");
                    $this->info("  Respuestas: {$question->answers->count()}");
                    $this->info("  Verificada: {$question->result_verified_at}");

                    $correctAnswers = $question->answers->where('is_correct', true)->count();
                    $totalPoints = $question->answers->sum('points_earned');
                    $this->info("  Respuestas correctas: {$correctAnswers}");
                    $this->info("  Total puntos: {$totalPoints}");
                }
            }

            return;
        }

        $this->info("Preguntas pendientes de verificación: {$pendingQuestions->count()}");

        foreach ($pendingQuestions as $question) {
            $this->info("\n--- Procesando pregunta ID: {$question->id} ---");
            $this->info("Título: {$question->title}");
            $this->info("Partido: {$question->football_match->home_team} vs {$question->football_match->away_team}");
            $this->info("Score: {$question->football_match->score}");
            $this->info("Respuestas: {$question->answers->count()}");

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

                $this->info("Respuestas correctas según OpenAI: " . implode(', ', $correctAnswers->toArray()));

                // Convertir las respuestas correctas de texto a IDs de opciones
                $correctOptionIds = [];
                foreach ($correctAnswers as $correctAnswerText) {
                    $option = $question->options->first(function($option) use ($correctAnswerText) {
                        return stripos($option->text, $correctAnswerText) !== false ||
                               stripos($correctAnswerText, $option->text) !== false;
                    });
                    if ($option) {
                        $correctOptionIds[] = $option->id;
                        $this->info("  Opción correcta encontrada: {$option->text} (ID: {$option->id})");
                    }
                }

                // Actualizar las respuestas correctas
                $updatedCount = 0;
                foreach ($answers as $answer) {
                    $wasCorrect = $answer->is_correct;
                    $oldPoints = $answer->points_earned;
                    if ($oldPoints === null) {
                        $oldPoints = 0;
                    }

                    $answer->is_correct = in_array($answer->option_id, $correctOptionIds);
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
                $this->error("❌ Error al verificar pregunta {$question->id}: " . $e->getMessage());
                continue;
            }
        }

        $this->info("\n=== VERIFICACIÓN COMPLETADA ===");
    }
}
