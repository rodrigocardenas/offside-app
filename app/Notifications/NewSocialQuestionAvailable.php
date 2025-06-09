<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use App\Models\Question;

class NewSocialQuestionAvailable extends Notification implements ShouldQueue
{
    use Queueable;

    protected $question;

    public function __construct(Question $question)
    {
        $this->question = $question;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast', 'web-push'];
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

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('Nueva pregunta social disponible')
            ->body('Hay una nueva pregunta social disponible en tu grupo: ' . $this->question->title)
            ->data([
                'question_id' => $this->question->id,
                'group_id' => $this->question->group_id,
                'url' => '/questions/' . $this->question->id
            ]);
    }
}
