<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * 🔧 MIGRACIÓN: Sincronizar puntos históricos
     *
     * Sincroniza todos los `answers.points_earned` acumulados a la tabla `group_user.points`
     * para que el sistema tenga un estado consistente antes de aplicar la sincronización
     * en tiempo real.
     *
     * Flujo:
     * 1. Para cada grupo, obtener todos los usuarios miembros
     * 2. Para cada usuario, calcular: SUM(answers.points_earned WHERE grupo)
     * 3. Actualizar group_user.points al total calculado
     * 4. Logging de cambios realizados
     */
    public function up(): void
    {
        Log::info('Starting migration: sync_historical_points_to_group_user');

        try {
            // Obtener todas las combinaciones de grupo-usuario con sus puntos calculados
            $userPointsInGroups = DB::table('group_user')
                ->join('users', 'group_user.user_id', '=', 'users.id')
                ->join('groups', 'group_user.group_id', '=', 'groups.id')
                ->select(
                    'group_user.id as pivot_id',
                    'group_user.group_id',
                    'group_user.user_id',
                    'group_user.points as current_points'
                )
                ->get();

            $syncedCount = 0;
            $skippedCount = 0;
            $totalPointsDiff = 0;

            foreach ($userPointsInGroups as $record) {
                // Calcular puntos totales de respuestas correctas en este grupo
                $totalPoints = DB::table('answers')
                    ->join('questions', 'answers.question_id', '=', 'questions.id')
                    ->where('answers.user_id', $record->user_id)
                    ->where('questions.group_id', $record->group_id)
                    ->sum('answers.points_earned');

                $totalPoints = (int) $totalPoints;

                // Si ya están sincronizados, saltar
                if ($totalPoints == $record->current_points) {
                    $skippedCount++;
                    continue;
                }

                // Actualizar el pivote
                $pointsDiff = $totalPoints - $record->current_points;
                $totalPointsDiff += abs($pointsDiff);

                DB::table('group_user')
                    ->where('id', $record->pivot_id)
                    ->update(['points' => $totalPoints]);

                $syncedCount++;

                Log::info('Synced user points in group', [
                    'user_id' => $record->user_id,
                    'group_id' => $record->group_id,
                    'old_points' => $record->current_points,
                    'new_points' => $totalPoints,
                    'points_diff' => $pointsDiff,
                ]);
            }

            Log::info('Migration: sync_historical_points_to_group_user completed', [
                'total_users_synced' => $syncedCount,
                'total_users_skipped' => $skippedCount,
                'total_points_changed' => $totalPointsDiff,
            ]);

        } catch (\Exception $e) {
            Log::error('Migration failed: sync_historical_points_to_group_user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Reverse: restaurar puntos a 0 (no recomendado en producción)
     */
    public function down(): void
    {
        Log::warning('Rolling back migration: sync_historical_points_to_group_user');

        // IMPORTANTE: No restauramos a 0 directamente para evitar pérdida de datos
        // Si se necesita revertir, se recomienda hacer restore desde backup
        Log::warning('ROLLBACK WARNING: This rollback does NOT revert changes to group_user.points');
        Log::warning('For safety, data should not be modified. If rollback needed, restore from database backup.');
    }
};
