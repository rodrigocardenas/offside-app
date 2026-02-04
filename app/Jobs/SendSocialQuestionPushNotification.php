<?php

namespace App\Jobs;

use App\Models\Question;
use App\Traits\HandlesPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSocialQuestionPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesPushNotifications;

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

        try {
            $this->sendPushNotificationToGroupUsers(
                $question->group,
                'Nueva pregunta disponible!',
                'Hay una nueva pregunta disponible en tu grupo: ' . $question->title,
                [
                    'link' => url('/groups/' . $question->group_id . '#questionsSection'),
                    'question_id' => (string) $question->id,
                    'group_id' => (string) $question->group_id,
                    'type' => 'social_question'
                ],
                $question->user_id
            );
        } catch (\Exception $e) {
            Log::error('Error en SendSocialQuestionPushNotification: ' . $e->getMessage());
        }
    }
}
