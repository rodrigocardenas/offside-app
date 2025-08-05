<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Question;
use App\Models\Answer;
use App\Models\FootballMatch;
use App\Services\OpenAIService;

class CheckPointsIssue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:points-issue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check specific points assignment issue';

    /**
     * Execute the console command.
     */
    public function handle(OpenAIService $openAIService)
    {
        $this->info("=== REVISIÓN DEL PROBLEMA DE PUNTOS ===\n");

        // 1. Buscar preguntas verificadas con respuestas
        $questionsWithAnswers = Question::whereHas('answers')
            ->whereNotNull('result_verified_at')
            ->whereHas('football_match', function($query) {
                $query->where('created_at', '>', '2025-08-01')->whereIn('status', ['FINISHED', 'Match Finished']);
            })
            ->with(['answers.user', 'answers.questionOption', 'options', 'football_match'])
            ->get();

        $this->info("Preguntas verificadas con respuestas: {$questionsWithAnswers->count()}");

        if ($questionsWithAnswers->isEmpty()) {
            $this->warn("No hay preguntas verificadas con respuestas.");

            // Buscar preguntas sin verificar que deberían tener respuestas
            $pendingQuestions = Question::whereHas('answers')
                ->whereNull('result_verified_at')
                ->whereHas('football_match', function($query) {
                    $query->where('created_at', '>', '2025-08-01')->whereIn('status', ['FINISHED', 'Match Finished']);
                })
                ->with(['answers.user', 'answers.questionOption', 'options', 'football_match'])
                ->get();

            $this->info("Preguntas pendientes de verificación: {$pendingQuestions->count()}");

            if ($pendingQuestions->isNotEmpty()) {
                $this->info("\nPreguntas que necesitan verificación:");
                foreach ($pendingQuestions as $question) {
                    $this->info("ID: {$question->id} - {$question->title}");
                    $this->info("  Partido: {$question->football_match->home_team} vs {$question->football_match->away_team}");
                    $this->info("  Score: {$question->football_match->score}");
                    $this->info("  Respuestas: {$question->answers->count()}");
                }
            }

            return;
        }

        // 2. Revisar cada pregunta verificada
        foreach ($questionsWithAnswers as $question) {
            $this->info("\nPregunta ID: {$question->id}");
            $this->info("Título: {$question->title}");
            $this->info("Partido: {$question->football_match->home_team} vs {$question->football_match->away_team}");
            $this->info("Score: {$question->football_match->score}");
            $this->info("Verificada en: {$question->result_verified_at}");

            $correctAnswers = 0;
            $totalPoints = 0;
            $answersWithoutPoints = 0;

            foreach ($question->answers as $answer) {
                $userName = $answer->user ? $answer->user->name : 'Usuario desconocido';
                $optionText = $answer->questionOption ? $answer->questionOption->text : 'Opción desconocida';
                $isCorrect = $answer->is_correct ? '✅' : '❌';
                $points = $answer->points_earned ?? 0;

                $this->info("  - {$userName}: {$optionText} {$isCorrect} ({$points} puntos)");

                if ($answer->is_correct) {
                    $correctAnswers++;
                    if ($points == 0) {
                        $answersWithoutPoints++;
                        $this->warn("    ⚠️  Usuario acertó pero no tiene puntos asignados!");
                    }
                }
                $totalPoints += $points;
            }

            $this->info("Respuestas correctas: {$correctAnswers}");
            $this->info("Total de puntos asignados: {$totalPoints}");

            if ($answersWithoutPoints > 0) {
                $this->error("❌ PROBLEMA ENCONTRADO: {$answersWithoutPoints} respuestas correctas sin puntos asignados");
            }
        }

        // 3. Verificar si hay un job pendiente para procesar
        $this->info("\n=== VERIFICANDO JOB PENDIENTE ===");

        $pendingQuestions = Question::whereHas('answers')
            ->whereNull('result_verified_at')
            ->whereHas('football_match', function($query) {
                $query->whereIn('status', ['FINISHED', 'Match Finished']);
            })
            ->count();

        $this->info("Preguntas pendientes de verificación: {$pendingQuestions}");

        if ($pendingQuestions > 0) {
            $this->warn("Hay preguntas pendientes de verificación. Ejecuta el job para procesarlas.");
        }
    }
}
