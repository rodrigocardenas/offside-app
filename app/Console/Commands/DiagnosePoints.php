<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Question;
use App\Models\Answer;
use App\Models\FootballMatch;
use App\Services\OpenAIService;

class DiagnosePoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diagnose:points {question_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose points assignment issues';

    /**
     * Execute the console command.
     */
    public function handle(OpenAIService $openAIService)
    {
        $questionId = $this->argument('question_id');

        if ($questionId) {
            $questions = Question::where('id', $questionId)->get();
        } else {
            // Obtener preguntas verificadas recientemente
            $questions = Question::whereNotNull('result_verified_at')
                ->whereHas('football_match', function($query) {
                    $query->whereIn('status', ['FINISHED', 'Match Finished']);
                })
                ->orderBy('result_verified_at', 'desc')
                ->take(5)
                ->get();
        }

        $this->info("=== DIAGNÓSTICO DE ASIGNACIÓN DE PUNTOS ===\n");

        foreach ($questions as $question) {
            $this->info("Pregunta ID: {$question->id}");
            $this->info("Título: {$question->title}");
            $this->info("Tipo: {$question->type}");
            $this->info("Verificada en: {$question->result_verified_at}");

            if ($question->football_match) {
                $match = $question->football_match;
                $this->info("Partido: {$match->home_team} vs {$match->away_team}");
                $this->info("Score: {$match->score}");
                $this->info("Estado: {$match->status}");
            }

            // Verificar respuestas
            $answers = $question->answers()->with(['user', 'questionOption'])->get();
            $this->info("Total de respuestas: {$answers->count()}");

            $correctAnswers = 0;
            $totalPoints = 0;

            foreach ($answers as $answer) {
                $userName = $answer->user ? $answer->user->name : 'Usuario desconocido';
                $optionText = $answer->questionOption ? $answer->questionOption->text : 'Opción desconocida';
                $isCorrect = $answer->is_correct ? '✅' : '❌';
                $points = $answer->points_earned ?? 0;

                $this->info("  - {$userName}: {$optionText} {$isCorrect} ({$points} puntos)");

                if ($answer->is_correct) {
                    $correctAnswers++;
                }
                $totalPoints += $points;
            }

            $this->info("Respuestas correctas: {$correctAnswers}");
            $this->info("Total de puntos asignados: {$totalPoints}");

            // Verificar opciones correctas según la pregunta
            $this->info("\nOpciones de la pregunta:");
            foreach ($question->options as $option) {
                $isCorrect = $option->is_correct ? '✅' : '❌';
                $this->info("  - {$option->text} {$isCorrect}");
            }

            $this->info("\n" . str_repeat("-", 50) . "\n");
        }
    }
}
