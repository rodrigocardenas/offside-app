<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;

trait HandlesPushNotifications
{
    /**
     * Obtiene el servicio de mensajería de Firebase
     */
    protected function getFirebaseMessaging()
    {
        $credentials_path = base_path("storage/app/offside-dd226-firebase-adminsdk-fbsvc-54f29fd43f.json");

        if (!file_exists($credentials_path)) {
            Log::error('Archivo de credenciales de Firebase no encontrado en: ' . $credentials_path);
            throw new \Exception('Firebase credentials not found');
        }

        try {
            $factory = (new Factory)->withServiceAccount($credentials_path);
            return $factory->createMessaging();
        } catch (\Throwable $e) {
            Log::error('Error al inicializar Firebase: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envía notificación a todos los usuarios de un grupo
     */
    protected function sendPushNotificationToGroupUsers(
        $group,
        $title,
        $body,
        $data = [],
        $excludeUserId = null
    ) {
        try {
            error_log('[PUSH] sendPushNotificationToGroupUsers START group=' . $group->id . ' exclude=' . $excludeUserId);

            $messaging = $this->getFirebaseMessaging();
            $groupUsers = $group->users()
                ->when($excludeUserId, function ($query) use ($excludeUserId) {
                    return $query->where('users.id', '!=', $excludeUserId);
                })
                ->get();

            error_log('[PUSH] Usuarios encontrados: ' . $groupUsers->count() . ' -> ' . $groupUsers->pluck('id')->implode(', '));

            Log::info('Usuarios a notificar para grupo', [
                'group_id' => $group->id,
                'users' => $groupUsers->pluck('name')->toArray(),
                'excluded_user' => $excludeUserId
            ]);

            $successCount = 0;
            $failureCount = 0;

            foreach ($groupUsers as $user) {
                $subCount = $user->pushSubscriptions()->count();
                error_log('[PUSH] User ' . $user->id . ' (' . $user->name . '): ' . $subCount . ' subscriptions');

                $userSuccessCount = $this->sendPushNotificationToUser(
                    $messaging,
                    $user,
                    $title,
                    $body,
                    $data
                );
                $successCount += $userSuccessCount;
                $failureCount += ($subCount - $userSuccessCount);
            }

            error_log('[PUSH] DONE group=' . $group->id . ' success=' . $successCount . ' failures=' . $failureCount);

            Log::info('Notificaciones enviadas', [
                'group_id' => $group->id,
                'success' => $successCount,
                'failures' => $failureCount
            ]);

            return ['success' => $successCount, 'failures' => $failureCount];
        } catch (\Exception $e) {
            error_log('[PUSH] EXCEPTION: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            Log::error('Error enviando notificaciones al grupo: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envía notificación a un usuario específico
     */
    protected function sendPushNotificationToUser(
        $messaging,
        User $user,
        $title,
        $body,
        $data = []
    ) {
        $successCount = 0;

        foreach ($user->pushSubscriptions as $subscription) {
            try {
                error_log('[PUSH] Sending to user=' . $user->id . ' platform=' . $subscription->platform . ' token=' . substr($subscription->device_token, 0, 25) . '...');

                $message = [
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data,
                    'token' => $subscription->device_token,
                ];

                // Para Capacitor Android/iOS, agregar opciones adicionales
                if (in_array($subscription->platform, ['android', 'ios'])) {
                    $message['android'] = [
                        'priority' => 'high',
                        'notification' => [
                            'channelId' => 'high_importance_channel',
                            'title' => $title,
                            'body' => $body,
                            'icon' => 'ic_notification',
                            // No clickAction: dejar que FCM/Capacitor abra la app por defecto
                            // Los datos de navegación van en $data (link, type)
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

                $messaging->send($message);
                $successCount++;
                error_log('[PUSH] SUCCESS user=' . $user->id . ' platform=' . $subscription->platform);

                Log::info('Notificación enviada a usuario', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'platform' => $subscription->platform,
                    'device_token' => substr($subscription->device_token, 0, 20) . '...'
                ]);
            } catch (\Throwable $e) {
                error_log('[PUSH] FAILED user=' . $user->id . ' error=' . $e->getMessage());
                Log::error('Error enviando notificación FCM al usuario: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'platform' => $subscription->platform,
                ]);
            }
        }

        return $successCount;
    }
}
