<?php

namespace App\Jobs;

use App\Models\User;
use App\Traits\HandlesPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendDailyPointsEarnedPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesPushNotifications;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando SendDailyPointsEarnedPushNotification');

        // Obtener resumen de puntos ganados que aún no han sido notificados
        $summaries = DB::table('answers')
            ->join('users', 'answers.user_id', '=', 'users.id')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->join('groups', 'questions.group_id', '=', 'groups.id')
            ->where('answers.is_correct', true)
            ->where('answers.points_earned', '>', 0)
            ->whereNull('answers.notified_points_at')
            ->whereNotIn('groups.category', ['public', 'trivia'])
            ->select(
                'answers.user_id',
                DB::raw('SUM(answers.points_earned) as total_points'),
                DB::raw('COUNT(answers.id) as answer_count'),
                DB::raw('GROUP_CONCAT(DISTINCT groups.name ORDER BY groups.name SEPARATOR ", ") as group_names')
            )
            ->groupBy('answers.user_id')
            ->get();

        if ($summaries->isEmpty()) {
            Log::info('SendDailyPointsEarnedPushNotification: sin puntos pendientes de notificar');
            return;
        }

        try {
            $messaging = $this->getFirebaseMessaging();
        } catch (\Exception $e) {
            Log::error('Firebase init error en daily points: ' . $e->getMessage());
            return;
        }

        $totalNotified = 0;

        foreach ($summaries as $summary) {
            $user = User::find($summary->user_id);
            if (!$user || $user->pushSubscriptions->isEmpty()) {
                continue;
            }

            $points = (int) $summary->total_points;
            $groupNames = $summary->group_names;

            $title = "¡Has ganado {$points} puntos!";
            $body  = "Tus respuestas correctas en {$groupNames} te dan {$points} pts";

            $this->sendPushNotificationToUser(
                $messaging,
                $user,
                $title,
                $body,
                [
                    'type'   => 'daily_points_earned',
                    'link'   => url('/groups'),
                    'points' => (string) $points,
                ]
            );

            // Marcar las respuestas como notificadas
            DB::table('answers')
                ->join('questions', 'answers.question_id', '=', 'questions.id')
                ->join('groups', 'questions.group_id', '=', 'groups.id')
                ->where('answers.user_id', $summary->user_id)
                ->where('answers.is_correct', true)
                ->where('answers.points_earned', '>', 0)
                ->whereNull('answers.notified_points_at')
                ->whereNotIn('groups.category', ['public', 'trivia'])
                ->update(['answers.notified_points_at' => now()]);

            $totalNotified++;
        }

        Log::info('SendDailyPointsEarnedPushNotification completado', [
            'users_notified' => $totalNotified,
        ]);
    }
}
