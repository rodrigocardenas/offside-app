<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PreMatch;
use App\Models\Match;

echo "\n=== PRE MATCH MODAL TEST ===\n";

// Get match
$match = Match::orderBy('kick_off_time', 'desc')->first();
echo "Match: " . $match->id . " - " . $match->home_team->name . " vs " . $match->away_team->name . "\n";

// Create Pre Match
$pm = PreMatch::create([
    'match_id' => $match->id,
    'group_id' => 12,
    'created_by' => 1,
    'penalty_type' => 'POINTS',
    'penalty_points' => 1000,
    'status' => 'pending'
]);

echo "Created Pre Match ID: " . $pm->id . "\n";
echo "Stored match_id: " . $pm->match_id . "\n";

// Verify
$verify = PreMatch::find($pm->id);
if ($verify && $verify->match_id == $match->id) {
    echo "✅ VERIFICATION PASSED - match_id saved correctly\n";
} else {
    echo "❌ VERIFICATION FAILED\n";
}
