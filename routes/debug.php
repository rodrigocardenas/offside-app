<?php

use Illuminate\Support\Facades\Route;
use App\Models\PreMatch;
use App\Models\Match;

// DEBUG ROUTE - Test Pre Match Modal
Route::get('/debug/test-prematch', function () {
    try {
        echo "<h2>🧪 Pre Match Modal Test</h2>";

        // Get a match
        $match = Match::where('kick_off_time', '>', now())
            ->orderBy('kick_off_time')
            ->first() ?? Match::orderBy('id', 'desc')->first();

        if (!$match) {
            return "❌ No matches found";
        }

        echo "<p>✅ Match found: {$match->id} ({$match->home_team->name} vs {$match->away_team->name})</p>";

        // Create Pre Match
        $pm = PreMatch::create([
            'match_id' => $match->id,
            'group_id' => 12,
            'created_by' => 1,
            'penalty_type' => 'POINTS',
            'penalty_points' => 1000,
            'status' => 'pending'
        ]);

        echo "<p>✅ Pre Match created with ID: {$pm->id}</p>";
        echo "<p>   - match_id in DB: {$pm->match_id}</p>";

        // Verify
        $verify = PreMatch::find($pm->id);
        if ($verify && $verify->match_id == $match->id) {
            echo "<p><strong style='color:green'>✅ VERIFICATION PASSED</strong></p>";
            echo "<p>Pre Match is correctly stored with match_id = {$pm->match_id}</p>";
        } else {
            echo "<p><strong style='color:red'>❌ VERIFICATION FAILED</strong></p>";
        }

        echo "<pre>" . json_encode($pm->toArray(), JSON_PRETTY_PRINT) . "</pre>";

    } catch (\Exception $e) {
        return "❌ Error: " . $e->getMessage();
    }
});
