<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Question;
use App\Models\Answer;
use App\Models\FootballMatch;
use App\Models\User;
use App\Models\QuestionOption;
use App\Services\OpenAIService;

class SimulatePointsAssignment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simulate:points-assignment {question_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate points assignment for a specific question';

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

        $this->info("=== SIMULACIÓN DE ASIGNACIÓN DE PUNTOS ===\n");
        $this->info("Pregunta ID: {$question->id}");
        $this->info("Título: {$question->title}");
        $this->info("Tipo: {$question->type}");

        if ($question->football_match) {
            $match = $question->football_match;
            $this->info("Partido: {$match->home_team} vs {$match->away_team}");
            $this->info("Score: {$match->score}");
            $this->info("Estado: {$match->status}");
        }

        $this->info("Opciones disponibles:");
        foreach ($question->options as $option) {
            $this->info("  - ID: {$option->id}, Texto: {$option->text}");
        }

        $this->info("\nRespuestas actuales: {$question->answers->count()}");

        if ($question->answers->isEmpty()) {
            $this->warn("No hay respuestas para esta pregunta. Creando respuestas de prueba...");

            // Crear respuestas de prueba
            $users = User::take(3)->get();
            if ($users->isEmpty()) {
                $this->error("No hay usuarios en la base de datos para crear respuestas de prueba");
                return;
            }

            foreach ($users as $index => $user) {
                // Asignar una opción diferente a cada usuario
                $option = $question->options[$index % $question->options->count()];

                $answer = new Answer();
                $answer->user_id = $user->id;
                $answer->question_id = $question->id;
                $answer->question_option_id = $option->id;
                $answer->is_correct = false;
                $answer->points_earned = 0;
                $answer->save();

                $this->info("  Creada respuesta de prueba: {$user->name} -> {$option->text}");
            }

            // Recargar la pregunta con las nuevas respuestas
            $question->load(['answers.user', 'answers.questionOption', 'options', 'football_match']);
        }

        $this->info("\nEstado actual de las respuestas:");
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
                foreach ($question->answers as $answer) {
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

                // Mostrar resultado final
                $this->info("\n=== RESULTADO FINAL ===");
                $correctAnswers = $question->answers->where('is_correct', true)->count();
                $totalPoints = $question->answers->sum('points_earned');
                $this->info("Respuestas correctas: {$correctAnswers}");
                $this->info("Total de puntos asignados: {$totalPoints}");

            } catch (\Exception $e) {
                $this->error("❌ Error en verificación: " . $e->getMessage());
            }
        } else {
            $this->warn("El partido no está finalizado o no hay partido asociado");
        }
    }
}
