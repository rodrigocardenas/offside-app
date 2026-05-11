<?php

namespace App\Jobs;

use App\Models\ChatMessage;
use App\Traits\HandlesPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendChatPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesPushNotifications;

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

        $group = $chatMessage->group;
        if (in_array($group->category, ['public', 'trivia'])) {
            return;
        }

        try {
            $this->sendPushNotificationToGroupUsers(
                $group,
                'Nuevo mensaje en el grupo ' . $group->name,
                $chatMessage->user->name . ': ' . $chatMessage->message,
                [
                    'link' => url('/groups/' . $group->id . '#chatSection'),
                    'group_id' => (string) $group->id,
                    'message_id' => (string) $chatMessage->id,
                    'type' => 'chat_message'
                ],
                $chatMessage->user_id
            );
        } catch (\Exception $e) {
            Log::error('Error en SendChatPushNotification: ' . $e->getMessage());
        }
    }
}
