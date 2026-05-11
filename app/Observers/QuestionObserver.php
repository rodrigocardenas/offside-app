<?php

namespace App\Observers;

use App\Jobs\SendFeaturedQuestionPushNotification;
use App\Models\Question;

class QuestionObserver
{
    public function updated(Question $question): void
    {
        if ($question->wasChanged('is_featured') && $question->is_featured) {
            SendFeaturedQuestionPushNotification::dispatch($question->id);
        }
    }
}
