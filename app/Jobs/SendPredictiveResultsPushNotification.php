<?php

namespace App\Jobs;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;

class SendPredictiveResultsPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $groupId;
    protected $correctAnswers;
    protected $totalAnswers;

    /**
     * Create a new job instance.
     */
    public function __construct($groupId, $correctAnswers = 0, $totalAnswers = 0)
    {
        $this->groupId = $groupId;
        $this->correctAnswers = $correctAnswers;
        $this->totalAnswers = $totalAnswers;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $group = Group::find($this->groupId);
        if (!$group) {
            Log::warning('Group no encontrado para notificación push de resultados. ID: ' . $this->groupId);
            return;
        }

        $credentials_path = base_path("storage/app/offside-dd226-firebase-adminsdk-fbsvc-54f29fd43f.json");

        if (!file_exists($credentials_path)) {
            Log::error('Archivo de credenciales de Firebase no encontrado en: ' . $credentials_path);
            return;
        }

        try {
            $factory = (new Factory)->withServiceAccount($credentials_path);
            $messaging = $factory->createMessaging();
        } catch (\Throwable $e) {
            Log::error('Error al inicializar Firebase: ' . $e->getMessage());
            return;
        }

        // Obtener usuarios del grupo que tienen respuestas predictivas
        $groupUsers = $group->users;
        Log::info('Usuarios notificados para resultados predictivos', ['groupUsers' => $groupUsers->pluck('name')]);

        $accuracy = $this->totalAnswers > 0 ? round(($this->correctAnswers / $this->totalAnswers) * 100, 1) : 0;

        foreach ($groupUsers as $user) {
            foreach ($user->pushSubscriptions as $subscription) {
                $message = [
                    'notification' => [
                        'title' => '¡Resultados disponibles!',
                        'body' => "Tus predicciones en {$group->name} están listas. Precisión: {$accuracy}%",
                    ],
                    'data' => [
                        'link' => url('/groups/' . $group->id . '/predictive-results'),
                        'group_id' => (string) $group->id,
                        'correct_answers' => (string) $this->correctAnswers,
                        'total_answers' => (string) $this->totalAnswers,
                        'accuracy' => (string) $accuracy,
                        'type' => 'predictive_results'
                    ],
                    'webpush' => [
                        'headers' => [
                            'Urgency' => 'high',
                        ],
                        'notification' => [
                            'icon' => '/images/logo_white_bg.png',
                            'click_action' => url('/groups/' . $group->id . '/predictive-results'),
                        ],
                        'fcm_options' => [
                            'link' => url('/groups/' . $group->id . '/predictive-results'),
                        ],
                    ],
                    'token' => $subscription->device_token,
                ];

                try {
                    $messaging->send($message);
                    Log::info('Notificación de resultados predictivos enviada a ' . $user->name, [
                        'group_id' => $group->id,
                        'accuracy' => $accuracy
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Error enviando notificación FCM de resultados predictivos: ' . $e->getMessage());
                }
            }
        }
    }
}
