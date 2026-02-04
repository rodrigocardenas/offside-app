<?php

namespace App\Jobs;

use App\Models\Group;
use App\Traits\HandlesPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPredictiveResultsPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesPushNotifications;

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

        $accuracy = $this->totalAnswers > 0 ? round(($this->correctAnswers / $this->totalAnswers) * 100, 1) : 0;

        try {
            $this->sendPushNotificationToGroupUsers(
                $group,
                '¡Resultados disponibles!',
                "Tus predicciones en {$group->name} están listas. Precisión: {$accuracy}%",
                [
                    'link' => url('/groups/' . $group->id . '/predictive-results'),
                    'group_id' => (string) $group->id,
                    'correct_answers' => (string) $this->correctAnswers,
                    'total_answers' => (string) $this->totalAnswers,
                    'accuracy' => (string) $accuracy,
                    'type' => 'predictive_results'
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error en SendPredictiveResultsPushNotification: ' . $e->getMessage());
        }
    }
}
