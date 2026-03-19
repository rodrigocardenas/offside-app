<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Console\Command;

class VerifyGroupData extends Command
{
    protected $signature = 'verify:group-data {--group=129} {--match-id=}';
    protected $description = 'Verifica y muestra el estado de datos de un grupo';

    public function handle()
    {
        $groupId = $this->option('group');
        $matchId = $this->option('match-id');

        $this->info("═══════════════════════════════════════════════════════");
        $this->info("VERIFICACIÓN DE DATOS - GRUPO {$groupId}");
        $this->info("═══════════════════════════════════════════════════════\n");

        // Obtener grupo
        $group = Group::with('questions.options', 'questions.football_match', 'questions.answers')
            ->find($groupId);

        if (!$group) {
            $this->error("Grupo {$groupId} no encontrado");
            return;
        }

        $this->line("Grupo: {$group->name}");
        $this->line("Competencia: {$group->competition->name}");
        $this->line("Total preguntas: {$group->questions->count()}");

        // Filtrar por match si se especifica
        $questions = $group->questions;
        if ($matchId) {
            $questions = $questions->where('match_id', $matchId);
            $this->line("Filtrado por Match: {$matchId}");
        }

        $this->line("\n" . str_repeat("─", 130));

        // Procesar cada pregunta
        foreach ($questions as $question) {
            $match = $question->football_match;
            $totalAnswers = $question->answers->count();
            $correctAnswerCount = $question->answers->where('question_option_id', '<>', null)->count();

            $this->line("\n📝 PREGUNTA {$question->id}");
            $this->line("   Texto: " . substr($question->title, 0, 80));
            $this->line("   Match: {$match->home_team} vs {$match->away_team} ({$match->date})");
            $this->line("   Resultado: {$match->score->home_score} - {$match->score->away_score}");
            $this->line("   Respuestas de usuarios: {$totalAnswers}");
            if ($match->statistics && isset($match->statistics['possession'])) {
                $this->line("   📊 Datos: Posesión - {json_encode($match->statistics['possession'])}");
            }

            // Mostrar opciones
            $this->line("\n   OPCIONES:");
            foreach ($question->options as $option) {
                $answerCount = $question->answers->where('question_option_id', $option->id)->count();
                $isCorrect = $option->is_correct ? '✓' : '✗';
                $this->line("   {$isCorrect} Opción {$option->id}: {$option->text}");
                $this->line("      Usuarios seleccionaron: {$answerCount}");

                if ($option->is_correct) {
                    $correctUsers = $question->answers->where('question_option_id', $option->id);
                    $totalPoints = $correctUsers->sum('points_earned');
                    $this->line("      👥 {$correctUsers->count()} usuarios acertaron, {$totalPoints} puntos otorgados");
                }
            }

            // Resumen de respuestas incorrectas
            $noAnswerCount = $question->answers->where('question_option_id', null)->count();
            if ($noAnswerCount > 0) {
                $this->line("   ⚠️  {$noAnswerCount} usuarios sin respuesta correcta");
            }

            $this->line("");
        }

        $this->line("\n" . str_repeat("═", 130));
        $this->info("Inspección completada");
    }
}
