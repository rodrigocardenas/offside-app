<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use App\Models\Group;
use App\Jobs\SendNewPredictiveQuestionsPushNotification;

class NewPredictiveQuestionsAvailable extends Notification implements ShouldQueue
{
    use Queueable;

    protected $group;
    protected $questionCount;

    public function __construct(Group $group, $questionCount = 0)
    {
        $this->group = $group;
        $this->questionCount = $questionCount;
    }

    public function toArray($notifiable)
    {
        return [
            'title' => '¡Nuevas preguntas disponibles!',
            'body' => "Hay {$this->questionCount} nuevas preguntas predictivas en {$this->group->name}",
            'group_id' => $this->group->id,
            'question_count' => $this->questionCount,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title' => '¡Nuevas preguntas disponibles!',
            'body' => "Hay {$this->questionCount} nuevas preguntas predictivas en {$this->group->name}",
            'group_id' => $this->group->id,
            'question_count' => $this->questionCount,
        ]);
    }

    /**
     * Enviar notificación push usando Firebase FCM
     */
    public function toFirebase($notifiable)
    {
        // Despachar job para enviar notificación push
        SendNewPredictiveQuestionsPushNotification::dispatch($this->group->id, $this->questionCount);
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
