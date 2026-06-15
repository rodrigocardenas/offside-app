<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FootballMatch;
use App\Services\FootballService;
use App\Jobs\VerifyAllQuestionsJob;
use Illuminate\Support\Str;

$service = app(FootballService::class);

// Get all matches that happened before 2 hours ago
$matches = FootballMatch::where('league', 'WC')
    ->where('date', '<=', now()->subHours(2))
    ->get();

$updatedIds = [];

foreach ($matches as $match) {
    echo "Updating Match {$match->id} ({$match->home_team} vs {$match->away_team})...\n";
    try {
        $service->updateMatchFromApi($match->id);
        
        // Reload
        $m = FootballMatch::find($match->id);
        if (in_array($m->status, ['Match Finished', 'Finished', 'FINISHED'])) {
            $updatedIds[] = $m->id;
        }
    } catch (\Exception $e) {
        echo "Failed to update match {$match->id}: " . $e->getMessage() . "\n";
    }
}

echo "Successfully updated " . count($updatedIds) . " matches.\n";

if (count($updatedIds) > 0) {
    echo "Dispatching verification job for " . count($updatedIds) . " matches...\n";
    $batchId = Str::uuid()->toString();
    
    // Set last verification attempt so they don't get picked up repeatedly
    FootballMatch::whereIn('id', $updatedIds)->update([
        'last_verification_attempt_at' => now()
    ]);
    
    // Instead of queueing, let's run it synchronously to guarantee it works immediately!
    $verifyJob = new VerifyAllQuestionsJob($updatedIds, $batchId);
    $verifyJob->handle();
    echo "Verification job handled synchronously!\n";
}
