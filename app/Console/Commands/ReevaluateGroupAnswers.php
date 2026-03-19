<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\Question;
use App\Models\Answer;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReevaluateGroupAnswers extends Command
{
    protected $signature = 'answers:reevaluate {--group=129} {--date=2026-03-10}';
    protected $description = 'Re-evalúa todas las respuestas de usuarios basándose en las opciones correctas actualizadas';

    public function handle()
    {
        $groupId = $this->option('group');
        $date = $this->option('date');

        $this->info("Re-evaluando respuestas del Grupo {$groupId} para fecha {$date}");

        // Obtener grupo
        $group = Group::find($groupId);
        if (!$group) {
            $this->error("Grupo {$groupId} no encontrado");
            return;
        }

        // Obtener preguntas del grupo para la fecha especificada
        $questions = Question::where('group_id', $groupId)
            ->whereHas('football_match', fn ($q) => $q->where('date', '>=', $date)->where('date', '<', Carbon::parse($date)->addDay()))
            ->get();

        if ($questions->isEmpty()) {
            $this->info("No hay preguntas encontradas para el grupo {$groupId} en la fecha {$date}");
            return;
        }

        $this->info("Encontradas " . $questions->count() . " preguntas");

        $totalAnswers = 0;
        $correctAnswers = 0;
        $updatedAnswers = 0;

        foreach ($questions as $question) {
            $this->line("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->line("Pregunta {$question->id}: " . substr($question->title, 0, 60));
            $this->line("Match: {$question->football_match->home_team} vs {$question->football_match->away_team}");

            // Obtener opciones correctas
            $correctOptions = $question->options()->where('is_correct', true)->pluck('id')->toArray();
            $this->line("Opciones correctas: " . implode(', ', $correctOptions));

            // Procesar todas las respuestas
            $answers = $question->answers()->get();
            $this->line("Total respuestas: " . $answers->count());

            foreach ($answers as $answer) {
                $totalAnswers++;
                $isCorrect = in_array($answer->question_option_id, $correctOptions);

                if ($isCorrect) {
                    $correctAnswers++;
                    $expectedPoints = 300; // Puntos por respuesta correcta
                } else {
                    $expectedPoints = 0;
                }

                // Si los puntos no coinciden, actualizar
                if ($answer->points_earned != $expectedPoints) {
                    $oldPoints = $answer->points_earned;
                    $answer->points_earned = $expectedPoints;
                    $answer->save();
                    $updatedAnswers++;

                    $this->line("  ✓ Usuario {$answer->user->name}: actualizado {$oldPoints} → {$expectedPoints} (correcta={$isCorrect})", 
                        $isCorrect ? 'info' : 'warn');
                } else {
                    $this->line("  ✓ Usuario {$answer->user->name}: {$expectedPoints} pts (ok)", 
                        $isCorrect ? 'info' : 'comment');
                }
            }
        }

        $this->info("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("RESUMEN:");
        $this->info("  Total respuestas: {$totalAnswers}");
        $this->info("  Respuestas correctas: {$correctAnswers}");
        $this->info("  Respuestas actualizadas: {$updatedAnswers}");
        $this->info("  Porcentaje de acierto: " . ($totalAnswers > 0 ? round(($correctAnswers / $totalAnswers) * 100, 1) : 0) . "%");
    }
}
