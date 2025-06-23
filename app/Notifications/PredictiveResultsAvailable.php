<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use App\Models\Group;
use App\Jobs\SendPredictiveResultsPushNotification;

class PredictiveResultsAvailable extends Notification implements ShouldQueue
{
    use Queueable;

    protected $group;
    protected $correctAnswers;
    protected $totalAnswers;

    public function __construct(Group $group, $correctAnswers = 0, $totalAnswers = 0)
    {
        $this->group = $group;
        $this->correctAnswers = $correctAnswers;
        $this->totalAnswers = $totalAnswers;
    }

    public function toArray($notifiable)
    {
        $accuracy = $this->totalAnswers > 0 ? round(($this->correctAnswers / $this->totalAnswers) * 100, 1) : 0;

        return [
            'title' => '¡Resultados disponibles!',
            'body' => "Tus predicciones en {$this->group->name} están listas. Precisión: {$accuracy}%",
            'group_id' => $this->group->id,
            'correct_answers' => $this->correctAnswers,
            'total_answers' => $this->totalAnswers,
            'accuracy' => $accuracy,
        ];
    }

    public function toBroadcast($notifiable)
    {
        $accuracy = $this->totalAnswers > 0 ? round(($this->correctAnswers / $this->totalAnswers) * 100, 1) : 0;

        return new BroadcastMessage([
            'title' => '¡Resultados disponibles!',
            'body' => "Tus predicciones en {$this->group->name} están listas. Precisión: {$accuracy}%",
            'group_id' => $this->group->id,
            'correct_answers' => $this->correctAnswers,
            'total_answers' => $this->totalAnswers,
            'accuracy' => $accuracy,
        ]);
    }

    /**
     * Enviar notificación push usando Firebase FCM
     */
    public function toFirebase($notifiable)
    {
        // Despachar job para enviar notificación push
        SendPredictiveResultsPushNotification::dispatch($this->group->id, $this->correctAnswers, $this->totalAnswers);
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
