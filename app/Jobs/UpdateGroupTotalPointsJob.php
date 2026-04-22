<?php

namespace App\Jobs;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateGroupTotalPointsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('🚀 Starting UpdateGroupTotalPointsJob');

        $updatedCount = 0;
        $errorCount = 0;
        $totalPointsChanged = 0;

        try {
            // Usar chunking para evitar cargar todo en memoria
            Group::chunk(100, function ($groups) use (&$updatedCount, &$errorCount, &$totalPointsChanged) {
                foreach ($groups as $group) {
                    try {
                        $oldTotalPoints = $group->total_points;

                        // Calcular suma de puntos del grupo
                        $newTotalPoints = DB::table('group_user')
                            ->where('group_id', $group->id)
                            ->sum('points');

                        // Actualizar grupo
                        $group->update([
                            'total_points' => $newTotalPoints,
                            'total_points_updated_at' => now(),
                        ]);

                        $updatedCount++;

                        // Detectar cambios importantes
                        if ($newTotalPoints !== $oldTotalPoints) {
                            $totalPointsChanged++;
                            Log::debug('Group total points changed', [
                                'group_id' => $group->id,
                                'group_name' => $group->name,
                                'old_total' => $oldTotalPoints,
                                'new_total' => $newTotalPoints,
                                'difference' => $newTotalPoints - $oldTotalPoints,
                            ]);
                        }

                    } catch (\Exception $e) {
                        $errorCount++;
                        Log::error('❌ Error updating group total points', [
                            'group_id' => $group->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

            Log::info('✅ UpdateGroupTotalPointsJob completed', [
                'updated_count' => $updatedCount,
                'changed_count' => $totalPointsChanged,
                'error_count' => $errorCount,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ UpdateGroupTotalPointsJob failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
