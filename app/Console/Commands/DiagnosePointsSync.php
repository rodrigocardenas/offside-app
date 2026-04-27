<?php

namespace App\Console\Commands;

use App\Models\Answer;
use App\Models\Group;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnosePointsSync extends Command
{
    protected $signature = 'diagnose:points-sync {--group-id= : Diagnosticar grupo específico}';
    protected $description = 'Diagnosticar y mostrar discrepancias en sincronización de puntos';

    public function handle()
    {
        $this->info('🔍 DIAGNÓSTICO DE SINCRONIZACIÓN DE PUNTOS');
        $this->info('═══════════════════════════════════════════════════════');

        // Obtener grupos a diagnosticar
        $groupIds = $this->option('group-id')
            ? [$this->option('group-id')]
            : Group::pluck('id')->toArray();

        foreach ($groupIds as $groupId) {
            $this->diagnoseGroup($groupId);
        }
    }

    private function diagnoseGroup($groupId)
    {
        $group = Group::find($groupId);
        if (!$group) {
            $this->error("Grupo $groupId no encontrado");
            return;
        }

        $this->line("\n📊 Grupo: {$group->name} (ID: {$group->id})");
        $this->line('─────────────────────────────────────────────────');

        // Revisar cada usuario del grupo
        $users = $group->users()->get();
        $hasDiscrepancies = false;

        foreach ($users as $user) {
            $groupUserPoints = $user->pivot->points ?? 0;
            
            // Calcular puntos reales desde answers
            $answerPoints = Answer::where('user_id', $user->id)
                ->whereHas('question', function($q) use ($groupId) {
                    $q->where('group_id', $groupId);
                })
                ->sum('points_earned');

            $discrepancy = abs($groupUserPoints - $answerPoints);

            if ($discrepancy > 0) {
                $hasDiscrepancies = true;
                $this->warn("\n⚠️  DISCREPANCIA ENCONTRADA");
                $this->line("  Usuario: {$user->name} (ID: {$user->id})");
                $this->line("  Puntos en group_user: {$groupUserPoints}");
                $this->line("  Puntos reales (SUM answers): {$answerPoints}");
                $this->line("  DIFERENCIA: {$discrepancy} puntos");

                // Mostrar últimas respuestas
                $lastAnswers = Answer::where('user_id', $user->id)
                    ->whereHas('question', function($q) use ($groupId) {
                        $q->where('group_id', $groupId);
                    })
                    ->latest('id')
                    ->limit(3)
                    ->get();

                if ($lastAnswers->isNotEmpty()) {
                    $this->line("  📋 Últimas respuestas:");
                    foreach ($lastAnswers as $answer) {
                        $qText = $answer->question->title ?? 'Sin título';
                        $this->line("     - Q{$answer->question_id}: {$answerPoints} pts (Q: $qText)");
                    }
                }
            } else {
                $this->line("  ✅ {$user->name}: {$groupUserPoints} pts (OK)");
            }
        }

        if (!$hasDiscrepancies) {
            $this->info("\n✅ No se encontraron discrepancias en este grupo");
        }

        // Mostrar resumen
        $this->showGroupSummary($group);
    }

    private function showGroupSummary(Group $group)
    {
        $this->line("\n📈 RESUMEN DEL GRUPO");
        $this->line('─────────────────────────────────────────────────');

        // Total en group_user
        $totalGroupUser = DB::table('group_user')
            ->where('group_id', $group->id)
            ->sum('points');

        // Total real desde answers
        $totalAnswers = Answer::whereHas('question', function($q) use ($group) {
                $q->where('group_id', $group->id);
            })
            ->sum('points_earned');

        $this->line("Total en group_user.points: {$totalGroupUser} pts");
        $this->line("Total real (SUM answers): {$totalAnswers} pts");

        if ($totalGroupUser === $totalAnswers) {
            $this->info("✅ Totales coinciden");
        } else {
            $this->warn("⚠️  DISCREPANCIA TOTAL: " . abs($totalGroupUser - $totalAnswers) . " pts");
        }
    }
}
