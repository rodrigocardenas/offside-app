<?php

namespace App\Jobs;

use App\Models\User;
use App\Traits\HandlesPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendDailyUnanswerQuestionReminderPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesPushNotifications;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando SendDailyUnanswerQuestionReminderPushNotification');

        try {
            $messaging = $this->getFirebaseMessaging();
        } catch (\Exception $e) {
            Log::error('Firebase init error en daily unanswer reminder: ' . $e->getMessage());
            return;
        }

        $users = User::all();
        $totalNotified = 0;

        foreach ($users as $user) {
            if ($user->pushSubscriptions->isEmpty()) {
                continue;
            }

            $userGroupIds = $user->groups()
                ->whereNotIn('category', ['public', 'trivia'])
                ->pluck('groups.id');

            if ($userGroupIds->isEmpty()) {
                continue;
            }

            $unanswerCount = \App\Models\Question::whereIn('group_id', $userGroupIds)
                ->where('type', 'predictive')
                ->where('available_until', '>', now())
                ->whereDoesntHave('answers', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->count();

            if ($unanswerCount === 0) {
                continue;
            }

            $groupNames = $user->groups()
                ->whereNotIn('category', ['public', 'trivia'])
                ->limit(3)
                ->pluck('name')
                ->implode(', ');

            $title = '¡Tienes preguntas pendientes!';
            $body  = "Tienes {$unanswerCount} preguntas sin responder en {$groupNames}";

            $this->sendPushNotificationToUser(
                $messaging,
                $user,
                $title,
                $body,
                [
                    'type'               => 'daily_unanswer_reminder',
                    'link'               => url('/groups/' . $userGroupIds->first()),
                    'unanswer_questions' => (string) $unanswerCount,
                ]
            );

            $totalNotified++;
        }

        Log::info('SendDailyUnanswerQuestionReminderPushNotification completado', [
            'users_processed' => $users->count(),
            'users_notified'  => $totalNotified,
        ]);
    }
}
