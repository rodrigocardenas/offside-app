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

class SendFeaturedQuestionPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesPushNotifications;

    public function __construct(protected int $questionId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $question = Question::with('group')->find($this->questionId);

        if (!$question || !$question->group) {
            Log::warning('SendFeaturedQuestionPushNotification: pregunta o grupo no encontrado', [
                'question_id' => $this->questionId,
            ]);
            return;
        }

        $group = $question->group;

        if (in_array($group->category, ['public', 'trivia'])) {
            return;
        }

        $title = '¡Nueva pregunta destacada!';
        $body  = $question->title;

        $this->sendPushNotificationToGroupUsers(
            $group,
            $title,
            $body,
            [
                'type'        => 'featured_question',
                'link'        => url('/groups/' . $group->id),
                'group_id'    => (string) $group->id,
                'question_id' => (string) $question->id,
            ]
        );
    }
}
