<?php

namespace App\Jobs;

use App\Models\Group;
use App\Traits\HandlesPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendRankingOvertakenPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesPushNotifications;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando SendRankingOvertakenPushNotification');

        $skippedCategories = ['public', 'trivia'];

        $groups = Group::whereNotIn('category', $skippedCategories)
            ->whereNull('deleted_at')
            ->get();

        $totalNotified = 0;

        foreach ($groups as $group) {
            // Obtener ranking actual ordenado por puntos descendente
            $members = DB::table('group_user')
                ->where('group_id', $group->id)
                ->orderByDesc('points')
                ->get(['user_id', 'points', 'last_ranking_position']);

            if ($members->count() < 2) {
                continue;
            }

            try {
                $messaging = $this->getFirebaseMessaging();
            } catch (\Exception $e) {
                Log::error('Firebase init error en ranking overtaken: ' . $e->getMessage());
                continue;
            }

            foreach ($members as $index => $member) {
                $currentPosition = $index + 1;
                $previousPosition = $member->last_ranking_position;

                // Si tiene posición guardada y la actual es peor → fue superado
                if ($previousPosition !== null && $currentPosition > $previousPosition) {
                    $user = \App\Models\User::find($member->user_id);
                    if (!$user || $user->pushSubscriptions->isEmpty()) {
                        continue;
                    }

                    $title = '¡Te han superado en el ranking!';
                    $body = "En {$group->name} ahora estás en posición #{$currentPosition}";

                    $this->sendPushNotificationToUser(
                        $messaging,
                        $user,
                        $title,
                        $body,
                        [
                            'type'     => 'ranking_overtaken',
                            'link'     => url('/groups/' . $group->id . '/ranking'),
                            'group_id' => (string) $group->id,
                        ]
                    );

                    $totalNotified++;
                }
            }

            // Actualizar last_ranking_position para todos los miembros del grupo
            foreach ($members as $index => $member) {
                DB::table('group_user')
                    ->where('group_id', $group->id)
                    ->where('user_id', $member->user_id)
                    ->update(['last_ranking_position' => $index + 1]);
            }
        }

        Log::info('SendRankingOvertakenPushNotification completado', [
            'groups_processed' => $groups->count(),
            'users_notified'   => $totalNotified,
        ]);
    }
}
