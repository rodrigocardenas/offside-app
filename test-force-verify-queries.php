<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FootballMatch;
use App\Models\Question;

echo "═════════════════════════════════════════════════════════════\n";
echo "TESTING FORCE VERIFY COMMAND QUERIES\n";
echo "═════════════════════════════════════════════════════════════\n\n";

// TEST 1: Normal mode (pending questions only)
echo "📌 MODE 1: Normal (pending questions only)\n";
$reVerify = false;
$daysBack = 30;

$query1 = FootballMatch::query()
    ->whereIn('status', ['Match Finished', 'FINISHED', 'Finished']);

$windowStart = now()->subDays($daysBack);
$query1->where('date', '>=', $windowStart);

if ($reVerify) {
    $query1->whereHas('questions');
} else {
    $query1->whereHas('questions', function ($q) {
        $q->whereNull('result_verified_at');
    });
}

$matches1 = $query1->limit(100)->get();
echo "   Matches encontrados: " . $matches1->count() . "\n";
foreach ($matches1 as $m) {
    $pending = $m->questions()->whereNull('result_verified_at')->count();
    echo "   • Match #{$m->id}: {$m->home_team} vs {$m->away_team} ($pending pendientes)\n";
}

echo "\n";

// TEST 2: Re-verify mode (all questions)
echo "📌 MODE 2: Re-verify (all questions)\n";
$reVerify = true;

$query2 = FootballMatch::query()
    ->whereIn('status', ['Match Finished', 'FINISHED', 'Finished']);

$windowStart = now()->subDays($daysBack);
$query2->where('date', '>=', $windowStart);

if ($reVerify) {
    $query2->whereHas('questions');
} else {
    $query2->whereHas('questions', function ($q) {
        $q->whereNull('result_verified_at');
    });
}

$matches2 = $query2->limit(100)->get();
echo "   Matches encontrados: " . $matches2->count() . "\n";
foreach ($matches2->take(5) as $m) {
    $total = $m->questions()->count();
    $verified = $m->questions()->whereNotNull('result_verified_at')->count();
    echo "   • Match #{$m->id}: {$m->home_team} vs {$m->away_team} ($verified/$total verificadas)\n";
}

if ($matches2->count() > 5) {
    echo "   ... y " . ($matches2->count() - 5) . " más\n";
}

echo "\n═════════════════════════════════════════════════════════════\n";
