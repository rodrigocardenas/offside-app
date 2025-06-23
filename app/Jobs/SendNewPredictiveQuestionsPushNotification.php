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

class SendNewPredictiveQuestionsPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $groupId;
    protected $questionCount;

    /**
     * Create a new job instance.
     */
    public function __construct($groupId, $questionCount = 0)
    {
        $this->groupId = $groupId;
        $this->questionCount = $questionCount;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $group = Group::find($this->groupId);
        if (!$group) {
            Log::warning('Group no encontrado para notificación push de nuevas preguntas. ID: ' . $this->groupId);
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

        // Obtener usuarios del grupo
        $groupUsers = $group->users;
        Log::info('Usuarios notificados para nuevas preguntas predictivas', ['groupUsers' => $groupUsers->pluck('name')]);

        foreach ($groupUsers as $user) {
            foreach ($user->pushSubscriptions as $subscription) {
                $message = [
                    'notification' => [
                        'title' => '¡Nuevas preguntas disponibles!',
                        'body' => "Hay {$this->questionCount} nuevas preguntas predictivas en {$group->name}",
                    ],
                    'data' => [
                        'link' => url('/groups/' . $group->id),
                        'group_id' => (string) $group->id,
                        'question_count' => (string) $this->questionCount,
                        'type' => 'new_predictive_questions'
                    ],
                    'webpush' => [
                        'headers' => [
                            'Urgency' => 'high',
                        ],
                        'notification' => [
                            'icon' => '/images/logo_white_bg.png',
                            'click_action' => url('/groups/' . $group->id),
                        ],
                        'fcm_options' => [
                            'link' => url('/groups/' . $group->id),
                        ],
                    ],
                    'token' => $subscription->device_token,
                ];

                try {
                    $messaging->send($message);
                    Log::info('Notificación de nuevas preguntas predictivas enviada a ' . $user->name, [
                        'group_id' => $group->id,
                        'question_count' => $this->questionCount
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Error enviando notificación FCM de nuevas preguntas predictivas: ' . $e->getMessage());
                }
            }
        }
    }
}
