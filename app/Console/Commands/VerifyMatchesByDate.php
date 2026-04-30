<?php

namespace App\Console\Commands;

use App\Jobs\BatchGetScoresJob;
use App\Jobs\BatchExtractEventsJob;
use App\Jobs\VerifyAllQuestionsJob;
use App\Models\Answer;
use App\Models\FootballMatch;
use App\Models\Question;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VerifyMatchesByDate extends Command
{
    protected $signature = 'app:verify-matches-by-date
        {--date= : Fecha específica en formato Y-m-d (ej: 2026-04-20)}
        {--start-date= : Fecha inicio del rango (inclusive)}
        {--end-date= : Fecha fin del rango (inclusive)}
        {--group-id= : Filtrar solo preguntas de un grupo específico}
        {--re-verify : Resetear verificación y puntos antes de re-procesar (RECOMENDADO para corregir errores)}
        {--sync-points : Solo recalcular group_user.points desde la tabla answers (sin re-evaluar preguntas)}
        {--force : Saltar confirmación}';

    protected $description = 'Barrido de predicciones por rango de fechas: verifica respuestas y sincroniza puntos. Usar --re-verify para corregir errores de asignación.';

    public function handle(): int
    {
        $this->line("\n╔════════════════════════════════════════════════════════════════╗");
        $this->line("║  Barrido de Predicciones por Rango de Fechas                   ║");
        $this->line("╚════════════════════════════════════════════════════════════════╝\n");

        $date       = $this->option('date');
        $startDate  = $this->option('start-date');
        $endDate    = $this->option('end-date');
        $groupId    = $this->option('group-id');
        $reVerify   = $this->option('re-verify');
        $syncPoints = $this->option('sync-points');

        // ── Construir query de partidos ──────────────────────────────────────
        $query = FootballMatch::query();

        if ($date) {
            $query->whereDate('date', Carbon::createFromFormat('Y-m-d', $date)->startOfDay());
            $this->info("Rango: {$date}");
        } elseif ($startDate && $endDate) {
            $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
            $end   = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();
            $query->whereBetween('date', [$start, $end]);
            $this->info("Rango: {$startDate} → {$endDate}");
        } else {
            $this->error('Debes especificar --date o ambos --start-date y --end-date');
            return 1;
        }

        $matches = $query->orderBy('date')->get();

        if ($matches->isEmpty()) {
            $this->warn('No se encontraron partidos para las fechas especificadas.');
            return 0;
        }

        // ── Mostrar resumen ──────────────────────────────────────────────────
        $this->line("\nPartidos encontrados: {$matches->count()}");
        foreach ($matches as $match) {
            $qTotal    = $match->questions()->when($groupId, fn($q) => $q->where('group_id', $groupId))->count();
            $qPending  = $match->questions()->when($groupId, fn($q) => $q->where('group_id', $groupId))->whereNull('result_verified_at')->count();
            $qVerified = $qTotal - $qPending;

            $statusIcon = in_array($match->status, ['Match Finished', 'FINISHED', 'Finished']) ? '✅' : '⏳';
            $this->line(sprintf(
                "  %s %s  %s vs %s  [%s]  📋 preguntas: %d total (%d verificadas, %d pendientes)",
                $statusIcon,
                $match->date->format('Y-m-d'),
                $match->home_team,
                $match->away_team,
                $match->status,
                $qTotal,
                $qVerified,
                $qPending
            ));
        }

        if ($groupId) {
            $this->line("\n  🔍 Filtrando por grupo ID: {$groupId}");
        }

        $modeLabel = match(true) {
            $syncPoints => '🔄 SYNC-POINTS (recalcular group_user.points desde answers)',
            $reVerify   => '♻️  RE-VERIFY (resetear + re-evaluar todo)',
            default     => '▶️  NORMAL (solo preguntas pendientes sin verificar)',
        };
        $this->line("\n  Modo: {$modeLabel}");

        if (!$this->option('force') && !$this->confirm("\n¿Proceder?")) {
            $this->info('Operación cancelada.');
            return 0;
        }

        $matchIds = $matches->pluck('id')->all();

        // ── Modo: solo sincronizar puntos ────────────────────────────────────
        if ($syncPoints) {
            return $this->syncPointsFromAnswers($matchIds, $groupId);
        }

        // ── Modo: re-verificar (resetear antes de re-procesar) ───────────────
        if ($reVerify) {
            $this->resetQuestionsAndPoints($matchIds, $groupId);
        }

        // ── Despachar pipeline de verificación ──────────────────────────────
        $batchId = Str::uuid()->toString();

        FootballMatch::whereIn('id', $matchIds)->update([
            'last_verification_attempt_at' => now(),
        ]);

        $this->info("\n🚀 Despachando pipeline de verificación (batch: " . substr($batchId, 0, 8) . "...)");
        $this->line("   1. BatchGetScoresJob  (ahora)");
        $this->line("   2. BatchExtractEventsJob  (+60s)");
        $this->line("   3. VerifyAllQuestionsJob  (+120s)");

        dispatch(new BatchGetScoresJob($matchIds, $batchId, true));
        dispatch((new BatchExtractEventsJob($matchIds, $batchId))->delay(now()->addSeconds(60)));
        dispatch((new VerifyAllQuestionsJob($matchIds, $batchId))->delay(now()->addSeconds(120)));

        $this->line("\n╔════════════════════════════════════════════════════════════════╗");
        $this->line("║  RESUMEN                                                        ║");
        $this->line("╠════════════════════════════════════════════════════════════════╣");
        $this->line("║  Partidos: " . str_pad(count($matchIds), 3) . "   Batch: " . substr($batchId, 0, 8) . "...                        ║");
        $this->line("║  Los jobs se procesarán en cola. Revisa los logs para seguir.  ║");
        $this->line("╚════════════════════════════════════════════════════════════════╝\n");

        Log::info('VerifyMatchesByDate - pipeline dispatched', [
            'match_count' => count($matchIds),
            'batch_id'    => $batchId,
            're_verify'   => $reVerify,
            'group_id'    => $groupId,
        ]);

        return 0;
    }

    /**
     * Resetea result_verified_at en preguntas y descuenta puntos de group_user
     * antes de re-lanzar la evaluación.
     */
    private function resetQuestionsAndPoints(array $matchIds, ?string $groupId): void
    {
        $this->warn("\n♻️  RE-VERIFY: reseteando estado de verificación y puntos...");

        $questionsQuery = Question::whereIn('match_id', $matchIds)
            ->with('answers')
            ->when($groupId, fn($q) => $q->where('group_id', $groupId));

        $questions = $questionsQuery->get();
        $resetCount = 0;
        $pointsReverted = 0;

        foreach ($questions as $question) {
            foreach ($question->answers as $answer) {
                if ($answer->points_earned > 0 && $question->group_id) {
                    // Descontar puntos de group_user ANTES de resetear
                    DB::table('group_user')
                        ->where('group_id', $question->group_id)
                        ->where('user_id', $answer->user_id)
                        ->decrement('points', $answer->points_earned);

                    $pointsReverted += $answer->points_earned;
                }

                // Resetear answer
                $answer->is_correct    = false;
                $answer->points_earned = 0;
                $answer->save();
            }

            $resetCount++;
        }

        // Resetear result_verified_at para que VerifyAllQuestionsJob las reprocese
        $questionsQuery->update(['result_verified_at' => null]);

        // También resetear is_correct en question_options
        $questionIds = $questions->pluck('id')->all();
        if (!empty($questionIds)) {
            DB::table('question_options')
                ->whereIn('question_id', $questionIds)
                ->update(['is_correct' => false]);
        }

        $this->line("   → Preguntas reseteadas: {$resetCount}");
        $this->line("   → Puntos revertidos en group_user: {$pointsReverted}");

        Log::info('VerifyMatchesByDate - reset completed', [
            'questions_reset'  => $resetCount,
            'points_reverted'  => $pointsReverted,
            'match_ids'        => $matchIds,
        ]);
    }

    /**
     * Recalcula group_user.points sumando directamente desde la tabla answers.
     * No re-evalúa preguntas; solo corrige la columna de puntos en el pivote.
     */
    private function syncPointsFromAnswers(array $matchIds, ?string $groupId): int
    {
        $this->info("\n🔄 SYNC-POINTS: recalculando puntos desde tabla answers...");

        // Obtener todos los group_id afectados
        $groupIds = Question::whereIn('match_id', $matchIds)
            ->when($groupId, fn($q) => $q->where('group_id', $groupId))
            ->whereNotNull('group_id')
            ->distinct()
            ->pluck('group_id')
            ->all();

        if (empty($groupIds)) {
            $this->warn('No se encontraron preguntas con grupo para estos partidos.');
            return 0;
        }

        $this->line("   Grupos afectados: " . implode(', ', $groupIds));
        $totalUpdated = 0;

        foreach ($groupIds as $gid) {
            // Calcular puntos reales por usuario en este grupo
            $userPoints = DB::table('answers')
                ->join('questions', 'answers.question_id', '=', 'questions.id')
                ->where('questions.group_id', $gid)
                ->groupBy('answers.user_id')
                ->select('answers.user_id', DB::raw('SUM(answers.points_earned) as total'))
                ->get()
                ->keyBy('user_id');

            // Obtener todos los miembros del grupo
            $members = DB::table('group_user')->where('group_id', $gid)->get();

            foreach ($members as $member) {
                $realPoints = $userPoints->get($member->user_id)?->total ?? 0;
                $currentPoints = $member->points ?? 0;

                if ($realPoints != $currentPoints) {
                    DB::table('group_user')
                        ->where('group_id', $gid)
                        ->where('user_id', $member->user_id)
                        ->update(['points' => $realPoints]);

                    $diff = $realPoints - $currentPoints;
                    $sign = $diff > 0 ? '+' : '';
                    $this->line(sprintf(
                        "   Grupo %d | User %d: %d → %d (%s%d)",
                        $gid,
                        $member->user_id,
                        $currentPoints,
                        $realPoints,
                        $sign,
                        $diff
                    ));
                    $totalUpdated++;
                }
            }
        }

        if ($totalUpdated === 0) {
            $this->info('   ✅ Todos los puntos ya estaban sincronizados correctamente.');
        } else {
            $this->info("\n   ✅ Registros actualizados: {$totalUpdated}");
        }

        Log::info('VerifyMatchesByDate - sync-points completed', [
            'groups'          => $groupIds,
            'records_updated' => $totalUpdated,
            'match_ids'       => $matchIds,
        ]);

        return 0;
    }
}
