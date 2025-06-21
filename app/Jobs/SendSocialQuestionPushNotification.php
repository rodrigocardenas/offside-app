<?php

namespace App\Jobs;

use App\Models\Question;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;

class SendSocialQuestionPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $questionId;

    /**
     * Create a new job instance.
     */
    public function __construct($questionId)
    {
        $this->questionId = $questionId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $question = Question::with('group')->find($this->questionId);
        if (!$question) {
            Log::warning('Question no encontrada para notificación push. ID: ' . $this->questionId);
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

        // Obtener usuarios del grupo (excluyendo al creador de la pregunta)
        $groupUsers = $question->group->users()->where('users.id', '!=', $question->user_id)->get();
        Log::info('Usuarios notificados para pregunta social', ['groupUsers' => $groupUsers->pluck('name')]);

        foreach ($groupUsers as $user) {
            foreach ($user->pushSubscriptions as $subscription) {
                $message = [
                    'notification' => [
                        'title' => 'Nueva pregunta disponible!',
                        'body' => 'Hay una nueva pregunta disponible en tu grupo: ' . $question->title,
                    ],
                    'data' => [
                        'link' => url('/groups/' . $question->group_id . '#questionsSection'),
                        'question_id' => (string) $question->id,
                        'group_id' => (string) $question->group_id,
                        'type' => 'social_question'
                    ],
                    'webpush' => [
                        'headers' => [
                            'Urgency' => 'high',
                        ],
                        'notification' => [
                            'icon' => '/images/logo_white_bg.png',
                            'click_action' => url('/groups/' . $question->group_id . '#questionsSection'),
                        ],
                        'fcm_options' => [
                            'link' => url('/groups/' . $question->group_id . '#questionsSection'),
                        ],
                    ],
                    'token' => $subscription->device_token,
                ];

                try {
                    $messaging->send($message);
                    Log::info('Notificación de pregunta social enviada a ' . $user->name, ['question_id' => $question->id]);
                } catch (\Throwable $e) {
                    Log::error('Error enviando notificación FCM de pregunta social: ' . $e->getMessage());
                }
            }
        }
    }
}
