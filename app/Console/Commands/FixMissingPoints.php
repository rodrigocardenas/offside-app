<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Question;
use App\Models\Answer;
use App\Models\FootballMatch;
use App\Services\OpenAIService;

class FixMissingPoints extends Command
{
    protected $signature = 'fix:missing-points';
    protected $description = 'Fix missing points for verified questions';

    public function handle(OpenAIService $openAIService)
    {
        $this->info("=== REVISIÓN Y CORRECCIÓN DE PUNTOS FALTANTES ===\n");

        // Buscar preguntas verificadas con respuestas correctas pero sin puntos
        $questionsWithIssues = Question::whereHas('answers', function($query) {
                $query->where('is_correct', true)->where('points_earned', 0);
            })
            ->whereNotNull('result_verified_at')
            ->whereHas('football_match', function($query) {
                $query->whereIn('status', ['FINISHED', 'Match Finished']);
            })
            ->with(['answers.user', 'answers.questionOption', 'options', 'football_match'])
            ->get();

        if ($questionsWithIssues->isEmpty()) {
            $this->info("No se encontraron preguntas con respuestas correctas sin puntos asignados.");

            // Buscar preguntas verificadas en general
            $verifiedQuestions = Question::whereHas('answers')
                ->whereNotNull('result_verified_at')
                ->whereHas('football_match', function($query) {
                    $query->whereIn('status', ['FINISHED', 'Match Finished']);
                })
                ->with(['answers.user', 'answers.questionOption', 'options', 'football_match'])
                ->get();

            $this->info("Preguntas verificadas encontradas: {$verifiedQuestions->count()}");

            foreach ($verifiedQuestions as $question) {
                $correctAnswers = $question->answers->where('is_correct', true)->count();
                $totalPoints = $question->answers->sum('points_earned');
                $expectedPoints = $correctAnswers * 300;

                $this->info("Pregunta {$question->id}: {$correctAnswers} correctas, {$totalPoints} puntos (esperados: {$expectedPoints})");

                if ($totalPoints != $expectedPoints) {
                    $this->warn("  ⚠️  Discrepancia en puntos detectada!");
                }
            }

            return;
        }

        $this->info("Preguntas con problemas encontradas: {$questionsWithIssues->count()}");

        foreach ($questionsWithIssues as $question) {
            $this->info("\n--- Corrigiendo pregunta ID: {$question->id} ---");
            $this->info("Título: {$question->title}");
            $this->info("Partido: {$question->football_match->home_team} vs {$question->football_match->away_team}");
            $this->info("Score: {$question->football_match->score}");

            $correctAnswersWithoutPoints = $question->answers->where('is_correct', true)->where('points_earned', 0);
            $this->info("Respuestas correctas sin puntos: {$correctAnswersWithoutPoints->count()}");

            // Corregir puntos faltantes
            $fixedCount = 0;
            foreach ($correctAnswersWithoutPoints as $answer) {
                $answer->points_earned = 300;
                $answer->save();
                $fixedCount++;

                $userName = $answer->user ? $answer->user->name : 'Usuario desconocido';
                $this->info("  ✅ Corregido: {$userName} - 300 puntos asignados");
            }

            $this->info("Total de correcciones: {$fixedCount}");
        }

        $this->info("\n=== CORRECCIÓN COMPLETADA ===");

        // Verificar el resultado final
        $this->info("\nVerificando resultado final...");
        $finalQuestions = Question::whereHas('answers', function($query) {
                $query->where('is_correct', true)->where('points_earned', 0);
            })
            ->whereNotNull('result_verified_at')
            ->whereHas('football_match', function($query) {
                $query->whereIn('status', ['FINISHED', 'Match Finished']);
            })
            ->count();

        if ($finalQuestions == 0) {
            $this->info("✅ Todas las respuestas correctas ahora tienen puntos asignados.");
        } else {
            $this->warn("⚠️  Aún quedan {$finalQuestions} preguntas con problemas.");
        }
    }
}
