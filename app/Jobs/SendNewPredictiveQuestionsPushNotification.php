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

class SendNewPredictiveQuestionsPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesPushNotifications;

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

        try {
            $this->sendPushNotificationToGroupUsers(
                $group,
                '¡Nuevas preguntas disponibles!',
                "Hay {$this->questionCount} nuevas preguntas predictivas en {$group->name}",
                [
                    'link' => url('/groups/' . $group->id),
                    'group_id' => (string) $group->id,
                    'question_count' => (string) $this->questionCount,
                    'type' => 'new_predictive_questions'
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error en SendNewPredictiveQuestionsPushNotification: ' . $e->getMessage());
        }
    }
}
