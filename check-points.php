<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Answer;
use App\Models\Question;
use App\Models\FootballMatch;

// Get any recent finished matches
$matches = FootballMatch::whereIn('status', ['Finished', 'Match Finished', 'FINISHED'])
    ->orderByDesc('date')
    ->limit(3)
    ->with('questions.answers')
    ->get();

echo "Found " . $matches->count() . " recent finished matches\n\n";

foreach($matches as $m) {
    echo "═══════════════════════════════════════\n";
    echo "Match {$m->id}: {$m->home_team} vs {$m->away_team}\n";
    echo "Date: " . $m->date->format('Y-m-d H:i') . "\n";
    echo "═══════════════════════════════════════\n";
    
    $questions = $m->questions;
    echo "  Questions: " . $questions->count() . "\n\n";
    
    if($questions->count() > 0) {
        foreach($questions as $q) {
            $verified = $q->result_verified_at ? "✓" : "✗";
            $answers = $q->answers->count();
            $pointsSum = $q->answers->sum('points_earned');
            echo "    Q{$q->id} [$verified]: " . substr($q->text, 0, 50) . "...\n";
            echo "      Answers: {$answers}, Total points: {$pointsSum}\n";
        }
    }
    echo "\n";
}
