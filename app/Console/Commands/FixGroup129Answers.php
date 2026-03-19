<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\FootballMatch;
use App\Services\QuestionEvaluationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixGroup129Answers extends Command
{
    protected $signature = 'fix:group-129 {--match-id=2003} {--group-id=129} {--skip-inspection=false} {--force=false}';
    protected $description = 'Repara automáticamente Group 129 re-evaluando todas las respuestas';

    public function handle()
    {
        $groupId = (int) $this->option('group-id');
        $matchId = (int) $this->option('match-id');
        $skipInspection = (bool) $this->option('skip-inspection');
        $force = (bool) $this->option('force');

        $this->info("═══════════════════════════════════════════════════════════════");
        $this->info("REPARACIÓN AUTOMÁTICA: Group {$groupId}");
        $this->info("═══════════════════════════════════════════════════════════════\n");

        // Paso 1: Validar datos
        if (!$skipInspection) {
            $this->info("PASO 1: Validando datos...");
            if (!$this->validateData($groupId, $matchId)) {
                return;
            }
        }

        // Paso 2: Re-evaluar preguntas
        $this->info("\nPASO 2: Re-evaluando preguntas...");
        $updates = $this->reEvaluateQuestions($groupId, $matchId);

        if (empty($updates)) {
            $this->warn("No se encontraron cambios necesarios");
            return;
        }

        // Paso 3: Actualizar base de datos
        $this->info("\nPASO 3: Actualizando base de datos...");
        $this->applyUpdates($updates);

        // Paso 4: Recalcular respuestas de usuarios
        $this->info("\nPASO 4: Recalculando respuestas de usuarios...");
        $userUpdates = $this->recalculateUserAnswers($groupId, $matchId);

        // Mostrar resumen
        $this->showSummary($updates, $userUpdates);
    }

    private function validateData(int $groupId, int $matchId): bool
    {
        $group = \App\Models\Group::find($groupId);
        if (!$group) {
            $this->error("❌ Grupo {$groupId} no encontrado");
            return false;
        }

        $match = FootballMatch::find($matchId);
        if (!$match) {
            $this->error("❌ Match {$matchId} no encontrado");
            return false;
        }

        $this->info("✅ Grupo: {$group->name}");
        $this->info("✅ Match: {$match->home_team} vs {$match->away_team}");
        $this->info("✅ Resultado: {$match->home_team_score} - {$match->away_team_score}");

        if (!in_array($match->status, ['FINISHED', 'Match Finished', 'Finished'])) {
            $this->error("❌ Match no está finalizado (Status: {$match->status})");
            return false;
        }

        return true;
    }

    private function reEvaluateQuestions(int $groupId, int $matchId): array
    {
        $this->info("  Buscando preguntas...");

        $questions = Question::where('group_id', $groupId)
            ->where('match_id', $matchId)
            ->with('options')
            ->get();

        if ($questions->isEmpty()) {
            $this->warn("  No hay preguntas encontradas");
            return [];
        }

        $this->info("  Preguntas encontradas: {$questions->count()}");

        $service = new QuestionEvaluationService();
        $match = FootballMatch::find($matchId);
        $updates = [];

        foreach ($questions as $question) {
            $this->line("    Evaluando pregunta {$question->id}...");

            // Evaluar con reflection
            $reflection = new \ReflectionMethod(QuestionEvaluationService::class, 'evaluateQuestion');
            $reflection->setAccessible(true);

            try {
                $correctIds = $reflection->invoke($service, $question, $match);

                if (empty($correctIds)) {
                    $this->line("      ⚠️  No se pudo evaluar");
                    continue;
                }

                $currentCorrect = $question->options
                    ->where('is_correct', true)
                    ->pluck('id')
                    ->toArray();

                if (sort($correctIds) !== sort($currentCorrect)) {
                    $updates[$question->id] = [
                        'correct_ids' => $correctIds,
                        'current_ids' => $currentCorrect,
                        'options' => $question->options
                    ];
                    $this->line("      ✓ Cambios detectados");
                } else {
                    $this->line("      ✓ Sin cambios necesarios");
                }
            } catch (\Exception $e) {
                $this->error("      ❌ Error: " . $e->getMessage());
            }
        }

        return $updates;
    }

    private function applyUpdates(array $updates): void
    {
        $updateCount = 0;

        foreach ($updates as $questionId => $data) {
            $correctIds = $data['correct_ids'];

            // Remover todos los is_correct
            DB::table('question_options')
                ->where('question_id', $questionId)
                ->update(['is_correct' => false]);

            // Marcar correctos
            DB::table('question_options')
                ->where('question_id', $questionId)
                ->whereIn('id', $correctIds)
                ->update(['is_correct' => true]);

            $updateCount++;
            $this->line("  ✓ Pregunta {$questionId} actualizada");
        }

        $this->info("  Total actualizadas: {$updateCount}");
    }

    private function recalculateUserAnswers(int $groupId, int $matchId): array
    {
        $this->info("  Recalculando respuestas de usuarios...");

        $answers = DB::table('answers')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->join('question_options', 'answers.question_option_id', '=', 'question_options.id')
            ->where('questions.group_id', $groupId)
            ->where('questions.match_id', $matchId)
            ->select(
                'answers.id',
                'answers.user_id',
                'answers.question_id',
                'answers.points_earned',
                'question_options.is_correct'
            )
            ->get();

        $updates = [];
        $totalUpdated = 0;

        foreach ($answers as $answer) {
            $newPoints = $answer->is_correct ? 300 : 0;
            if ($answer->points_earned != $newPoints) {
                DB::table('answers')
                    ->where('id', $answer->id)
                    ->update(['points_earned' => $newPoints]);
                $totalUpdated++;
            }
        }

        $this->info("  Respuestas actualizadas: {$totalUpdated}");

        return ['total_answers' => count($answers), 'updated' => $totalUpdated];
    }

    private function showSummary(array $updates, array $userUpdates): void
    {
        $this->info("\n" . str_repeat("═", 65));
        $this->info("✅ REPARACIÓN COMPLETADA");
        $this->info("═" . str_repeat("─", 63) . "═");
        $this->info("  Preguntas modificadas: " . count($updates));
        $this->info("  Respuestas de usuarios: " . $userUpdates['total_answers']);
        $this->info("  Puntos recalculados: " . $userUpdates['updated']);
        $this->info("═" . str_repeat("─", 63) . "═\n");

        $this->info("Próximos pasos:");
        $this->info("  1. Verificar rankings de usuarios");
        $this->info("  2. Ejecutar: php artisan verify:group-data --group=129");
        $this->info("  3. Revisar tabla de puntos en base de datos\n");
    }
}
