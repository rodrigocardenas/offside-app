<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use App\Models\Question;
use App\Jobs\SendSocialQuestionPushNotification;

class NewSocialQuestionAvailable extends Notification implements ShouldQueue
{
    use Queueable;

    protected $question;

    public function __construct(Question $question)
    {
        $this->question = $question;
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Nueva pregunta social disponible',
            'body' => 'Hay una nueva pregunta social disponible en tu grupo: ' . $this->question->title,
            'question_id' => $this->question->id,
            'group_id' => $this->question->group_id,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title' => 'Nueva pregunta social disponible',
            'body' => 'Hay una nueva pregunta social disponible en tu grupo: ' . $this->question->title,
            'question_id' => $this->question->id,
            'group_id' => $this->question->group_id,
        ]);
    }

    /**
     * Enviar notificación push usando Firebase FCM
     */
    public function toFirebase($notifiable)
    {
        // Despachar job para enviar notificación push
        SendSocialQuestionPushNotification::dispatch($this->question->id);
    }

    /**
     * Determinar qué canales usar para la notificación
     */
    public function via($notifiable)
    {
        // Enviar notificación push usando Firebase
        $this->toFirebase($notifiable);

        return ['database', 'broadcast'];
    }
}
