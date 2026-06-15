<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Question;
use Illuminate\Support\Facades\DB;

$questions2027 = Question::where('match_id', 2027)->get();
$questions2028 = Question::where('match_id', 2028)->get();

function assignCorrectOptions($question, $correctOptionIds) {
    foreach ($question->options as $option) {
        $option->is_correct = in_array($option->id, $correctOptionIds);
        $option->save();
    }

    $groupId = $question->group_id;

    foreach ($question->answers as $answer) {
        $oldPointsEarned = $answer->points_earned;
        $answer->is_correct = in_array($answer->question_option_id, $correctOptionIds);
        $answer->points_earned = $answer->is_correct ? ($question->points ?? 300) : 0;
        $answer->save();

        $pointsDiff = $answer->points_earned - $oldPointsEarned;
        
        if ($pointsDiff !== 0) {
            $exists = DB::table('group_user')
                ->where('group_id', $groupId)
                ->where('user_id', $answer->user_id)
                ->exists();

            if ($exists) {
                if ($pointsDiff > 0) {
                    DB::table('group_user')
                        ->where('group_id', $groupId)
                        ->where('user_id', $answer->user_id)
                        ->increment('points', $pointsDiff);
                } elseif ($pointsDiff < 0) {
                    DB::table('group_user')
                        ->where('group_id', $groupId)
                        ->where('user_id', $answer->user_id)
                        ->update(['points' => DB::raw('GREATEST(0, points - ' . abs($pointsDiff) . ')')]);
                }
                echo "Assigned $pointsDiff points to User {$answer->user_id} for Q{$question->id} (Group $groupId).\n";
            }
        }
    }
}

foreach ($questions2027 as $q) {
    if (strpos(strtolower($q->title), 'resultado') !== false || strpos(strtolower($q->title), 'ganador') !== false) {
        $correctOptionIds = [];
        foreach($q->options as $opt) {
            $text = strtolower($opt->text);
            if (strpos($text, 'mexico') !== false || strpos($text, 'méxico') !== false) {
                $correctOptionIds[] = $opt->id;
            }
        }
        assignCorrectOptions($q, $correctOptionIds);
    } else {
        assignCorrectOptions($q, []);
    }
}

foreach ($questions2028 as $q) {
    assignCorrectOptions($q, []);
}

echo "All groups fixed for matches 2027 and 2028.\n";
