<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\Question;
use App\Models\FootballMatch;
use App\Services\QuestionEvaluationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;

class RepairGroupQuestions extends Command
{
    protected $signature = 'repair:group-questions 
                          {--group= : Group ID to repair}
                          {--date= : Date in format YYYY-MM-DD}
                          {--force : Apply changes without confirmation}';
    
    protected $description = 'Repara automáticamente preguntas de un grupo en una fecha específica re-evaluándolas';

    public function handle()
    {
        $groupId = $this->option('group');
        $date = $this->option('date');
        $force = $this->option('force');

        if (!$groupId || !$date) {
            $this->error('❌ Parámetros requeridos: --group=ID --date=YYYY-MM-DD');
            $this->info('Ejemplo: php artisan repair:group-questions --group=129 --date=2026-03-10');
            return;
        }

        $this->info('═══════════════════════════════════════════════════════');
        $this->info("REPARACIÓN DE PREGUNTAS - GRUPO {$groupId} | FECHA {$date}");
        $this->info('═══════════════════════════════════════════════════════\n');

        // Validar grupo
        $group = Group::find($groupId);
        if (!$group) {
            $this->error("❌ Grupo {$groupId} no encontrado");
            return;
        }

        $this->info("✅ Grupo: {$group->name}");

        // Obtener preguntas del grupo para la fecha
        $dateStart = Carbon::parse($date)->startOfDay();
        $dateEnd = Carbon::parse($date)->endOfDay();

        $questions = Question::where('group_id', $groupId)
            ->whereHas('football_match', function ($q) use ($dateStart, $dateEnd) {
                $q->whereBetween('date', [$dateStart, $dateEnd]);
            })
            ->with('football_match', 'options')
            ->get();

        if ($questions->isEmpty()) {
            $this->warn("⚠️  No hay preguntas del grupo {$groupId} en la fecha {$date}");
            return;
        }

        $this->info("📝 Preguntas encontradas: {$questions->count()}\n");

        // Paso 1: Inspeccionar estado actual
        $this->line('PASO 1: Inspeccionando estado actual de preguntas...\n');
        $questionUpdates = $this->inspectQuestions($questions);

        if (empty($questionUpdates)) {
            $this->info("✅ Todas las preguntas tienen opciones correctas marcadas. Sin cambios necesarios.");
            return;
        }

        // Mostrar cambios propuestos
        $this->showProposedChanges($questionUpdates);

        // Paso 2: Confirmar cambios
        if (!$force && !$this->confirm("\n¿Aplicar estos cambios?")) {
            $this->warn('Operación cancelada por usuario.');
            return;
        }

        // Paso 3: Aplicar cambios
        $this->line('\nPASO 2: Aplicando cambios en base de datos...\n');
        $this->applyChanges($questionUpdates);

        // Paso 4: Recalcular puntos
        $this->line('\nPASO 3: Recalculando puntos de usuarios...\n');
        $this->recalculateUserPoints($groupId, $date);

        $this->info('═══════════════════════════════════════════════════════');
        $this->info('✅ REPARACIÓN COMPLETADA EXITOSAMENTE');
        $this->info('═══════════════════════════════════════════════════════\n');
    }

    private function inspectQuestions($questions): array
    {
        $service = new QuestionEvaluationService();
        $reflection = new ReflectionMethod(QuestionEvaluationService::class, 'evaluateQuestion');
        $reflection->setAccessible(true);

        $updates = [];

        foreach ($questions as $question) {
            $match = $question->football_match;
            
            $this->line("  Evaluando Q{$question->id}: " . substr($question->title, 0, 50) . "...");

            // Evaluar con el servicio
            try {
                $evaluatedCorrectIds = $reflection->invoke($service, $question, $match);
            } catch (\Exception $e) {
                $this->warn("    ⚠️  Error en evaluación: " . $e->getMessage());
                $evaluatedCorrectIds = [];
            }

            if (empty($evaluatedCorrectIds)) {
                $this->line("    ⚠️  No se pudo evaluar");
                continue;
            }

            // Comparar con lo actual
            $currentCorrectIds = $question->options
                ->where('is_correct', true)
                ->pluck('id')
                ->toArray();

            if (sort($evaluatedCorrectIds) !== sort($currentCorrectIds)) {
                $updates[$question->id] = [
                    'question' => $question,
                    'match' => $match,
                    'evaluated_ids' => $evaluatedCorrectIds,
                    'current_ids' => $currentCorrectIds,
                ];
                
                $this->line("    🔄 CAMBIO NECESARIO");
            } else {
                $this->line("    ✓ Sin cambios");
            }
        }

        return $updates;
    }

    private function showProposedChanges($updates): void
    {
        $this->info("\n" . str_repeat('─', 130));
        $this->info('CAMBIOS PROPUESTOS:');
        $this->info(str_repeat('─', 130) . "\n");

        foreach ($updates as $qid => $data) {
            $question = $data['question'];
            $match = $data['match'];
            $evaluatedIds = $data['evaluated_ids'];
            $currentIds = $data['current_ids'];

            $this->line("📝 Pregunta {$qid}: {$question->title}");
            $this->line("   Match: {$match->home_team} vs {$match->away_team} ({$match->home_team_score}-{$match->away_team_score})");
            
            $this->line("   Opciones correctas actual: " . (empty($currentIds) ? 'NINGUNA' : implode(', ', $currentIds)));
            $this->line("   Opciones correctas evaluadas: " . implode(', ', $evaluatedIds));
            
            // Mostrar textos de opciones
            $this->line("   Detalles:");
            foreach ($question->options as $opt) {
                $isEvaluatedCorrect = in_array($opt->id, $evaluatedIds);
                $isCurrentCorrect = in_array($opt->id, $currentIds);
                
                $change = '';
                if ($isEvaluatedCorrect && !$isCurrentCorrect) {
                    $change = ' ← MARCARÁ COMO CORRECTA';
                } elseif (!$isEvaluatedCorrect && $isCurrentCorrect) {
                    $change = ' ← DESMARCARÁ';
                }
                
                $mark = $isEvaluatedCorrect ? '✅' : '  ';
                $this->line("     {$mark} [{$opt->id}] {$opt->text}{$change}");
            }
            
            $this->line("");
        }
    }

    private function applyChanges($updates): void
    {
        foreach ($updates as $qid => $data) {
            $question = $data['question'];
            $correctIds = $data['evaluated_ids'];

            // Marcar todos como falsos primero
            $question->options()->update(['is_correct' => false]);

            // Marcar correctos
            $question->options()
                ->whereIn('id', $correctIds)
                ->update(['is_correct' => true]);

            $this->line("  ✓ Pregunta {$qid} actualizada");
        }

        $this->info("\n  Total actualizadas: " . count($updates));
    }

    private function recalculateUserPoints($groupId, $date): void
    {
        $dateStart = Carbon::parse($date)->startOfDay();
        $dateEnd = Carbon::parse($date)->endOfDay();

        // Obtener preguntas de la fecha
        $questions = Question::where('group_id', $groupId)
            ->whereHas('football_match', function ($q) use ($dateStart, $dateEnd) {
                $q->whereBetween('date', [$dateStart, $dateEnd]);
            })
            ->get();

        $totalAnswers = 0;
        $correctAnswers = 0;
        $totalUpdated = 0;

        foreach ($questions as $question) {
            $correctOptions = $question->options()
                ->where('is_correct', true)
                ->pluck('id')
                ->toArray();

            $answers = $question->answers()->get();

            foreach ($answers as $answer) {
                $totalAnswers++;
                $isCorrect = in_array($answer->question_option_id, $correctOptions);

                if ($isCorrect) {
                    $correctAnswers++;
                    $expectedPoints = 300;
                } else {
                    $expectedPoints = 0;
                }

                if ($answer->points_earned != $expectedPoints) {
                    $oldPoints = $answer->points_earned;
                    $answer->points_earned = $expectedPoints;
                    $answer->save();
                    $totalUpdated++;
                }
            }

            $this->line("  ✓ Pregunta {$question->id}: {$correctAnswers} correctas");
        }

        $this->info("\n  ESTADÍSTICAS:");
        $this->info("    Total respuestas: {$totalAnswers}");
        $this->info("    Respuestas correctas: {$correctAnswers}");
        $this->info("    Respuestas actualizadas: {$totalUpdated}");
        if ($totalAnswers > 0) {
            $percentage = round(($correctAnswers / $totalAnswers) * 100, 1);
            $this->info("    Porcentaje de acierto: {$percentage}%");
        }
    }
}
