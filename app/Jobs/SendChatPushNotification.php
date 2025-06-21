<?php

namespace App\Jobs;

use App\Models\ChatMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;

class SendChatPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chatMessageId;

    /**
     * Create a new job instance.
     */
    public function __construct($chatMessageId)
    {
        $this->chatMessageId = $chatMessageId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $chatMessage = ChatMessage::find($this->chatMessageId);
        if (!$chatMessage) {
            Log::warning('ChatMessage no encontrado para notificación push. ID: ' . $this->chatMessageId);
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

        $groupUsers = $chatMessage->group->users()->where('users.id', '!=', $chatMessage->user_id)->get();
        Log::info('Usuarios notificados', ['groupUsers' => $groupUsers]);

        foreach ($groupUsers as $user) {
            foreach ($user->pushSubscriptions as $subscription) {
                $message = [
                    'notification' => [
                        'title' => 'Nuevo mensaje en el grupo ' . $chatMessage->group->name,
                        'body' => $chatMessage->user->name . ': ' . $chatMessage->message,
                    ],
                    'data' => [
                        'link' => url('/groups/' . $chatMessage->group->id . '#chatSection'),
                        'group_id' => (string) $chatMessage->group->id,
                        'message_id' => (string) $chatMessage->id,
                    ],
                    'webpush' => [
                        'headers' => [
                            'Urgency' => 'high',
                        ],
                        'notification' => [
                            'icon' => '/icon-192x192.png',
                            'click_action' => url('/groups/' . $chatMessage->group->id . '#chatSection'),
                        ],
                        'fcm_options' => [
                            'link' => url('/groups/' . $chatMessage->group->id . '#chatSection'),
                        ],
                    ],
                    'token' => $subscription->device_token,
                ];

                try {
                    $messaging->send($message);
                    Log::info('Notificación enviada a ' . $user->name, ['message' => $message]);
                } catch (\Throwable $e) {
                    Log::error('Error enviando notificación FCM: ' . $e->getMessage());
                }
            }
        }
    }
}
