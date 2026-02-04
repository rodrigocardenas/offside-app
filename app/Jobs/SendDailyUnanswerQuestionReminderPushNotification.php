<?php

namespace App\Jobs;

use App\Models\Group;
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
    public function handle()
    {
        Log::info('Iniciando SendDailyUnanswerQuestionReminderPushNotification');

        try {
            // Obtener todos los usuarios activos
            $users = User::where('is_active', true)
                ->orWhere('is_active', null)  // Incluir usuarios sin marcar estado
                ->get();

            $totalNotificationsSent = 0;
            $usersWithUnanswerQuestions = 0;

            foreach ($users as $user) {
                // Obtener todos los grupos del usuario
                $userGroups = $user->groups()->pluck('groups.id');

                if ($userGroups->isEmpty()) {
                    continue;
                }

                // Contar preguntas sin responder para este usuario en cada uno de sus grupos
                $unanswerQuestions = \App\Models\Question::whereIn('group_id', $userGroups)
                    ->where('type', 'predictive')
                    ->where('available_until', '>', now())  // Solo preguntas vigentes
                    ->whereDoesntHave('answers', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->count();

                if ($unanswerQuestions > 0) {
                    $usersWithUnanswerQuestions++;

                    // Obtener nombres de los grupos para el mensaje personalizado
                    $groupNames = $user->groups()
                        ->limit(3)
                        ->pluck('name')
                        ->implode(', ');

                    $title = '¡Tienes preguntas pendientes!';
                    $body = "Tienes {$unanswerQuestions} preguntas sin responder en {$groupNames}";

                    // Enviar notificación a este usuario
                    try {
                        $messaging = $this->getFirebaseMessaging();
                        $successCount = 0;

                        foreach ($user->pushSubscriptions as $subscription) {
                            $message = [
                                'notification' => [
                                    'title' => $title,
                                    'body' => $body,
                                ],
                                'data' => [
                                    'link' => url('/groups/' . $userGroups->first()),
                                    'unanswer_questions' => (string) $unanswerQuestions,
                                    'type' => 'daily_unanswer_reminder'
                                ],
                                'webpush' => [
                                    'headers' => [
                                        'Urgency' => 'high',
                                    ],
                                    'notification' => [
                                        'icon' => '/images/logo_white_bg.png',
                                        'click_action' => url('/groups/' . $userGroups->first()),
                                    ],
                                    'fcm_options' => [
                                        'link' => url('/groups/' . $userGroups->first()),
                                    ],
                                ],
                                'token' => $subscription->device_token,
                            ];

                            // Para Capacitor Android/iOS
                            if (in_array($subscription->platform, ['android', 'ios'])) {
                                $message['android'] = [
                                    'priority' => 'high',
                                    'notification' => [
                                        'channelId' => 'high_importance_channel',
                                        'title' => $title,
                                        'body' => $body,
                                        'icon' => 'icon',
                                        'clickAction' => url('/groups/' . $userGroups->first()),
                                    ],
                                ];
                                $message['apns'] = [
                                    'payload' => [
                                        'aps' => [
                                            'alert' => [
                                                'title' => $title,
                                                'body' => $body,
                                            ],
                                            'sound' => 'default',
                                            'badge' => 1,
                                        ],
                                        'mutableContent' => true,
                                    ],
                                ];
                            }

                            try {
                                $messaging->send($message);
                                $successCount++;

                                Log::info('Daily reminder enviado a usuario', [
                                    'user_id' => $user->id,
                                    'user_name' => $user->name,
                                    'platform' => $subscription->platform,
                                    'unanswer_questions' => $unanswerQuestions
                                ]);
                            } catch (\Throwable $e) {
                                Log::error('Error enviando daily reminder a usuario: ' . $e->getMessage(), [
                                    'user_id' => $user->id,
                                    'platform' => $subscription->platform,
                                ]);
                            }
                        }

                        $totalNotificationsSent += $successCount;
                    } catch (\Exception $e) {
                        Log::error('Error al obtener Firebase Messaging en daily reminder: ' . $e->getMessage(), [
                            'user_id' => $user->id
                        ]);
                    }
                }
            }

            Log::info('SendDailyUnanswerQuestionReminderPushNotification completado', [
                'users_processed' => $users->count(),
                'users_with_unanswer_questions' => $usersWithUnanswerQuestions,
                'total_notifications_sent' => $totalNotificationsSent
            ]);
        } catch (\Exception $e) {
            Log::error('Error en SendDailyUnanswerQuestionReminderPushNotification: ' . $e->getMessage());
        }
    }
}
