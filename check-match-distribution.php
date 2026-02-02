<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FootballMatch;

echo "═══════════════════════════════════════════════════════════════\n";
echo "ANÁLISIS: Matches disponibles\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Check recent finished matches
$finished = FootballMatch::whereIn('status', ['Match Finished', 'FINISHED', 'Finished'])
    ->orderByDesc('date')
    ->limit(5)
    ->get();

echo "Finished matches (últimos 5 más recientes):\n";
foreach($finished as $m) {
    $questions = $m->questions()->count();
    echo "  Match {$m->id}: {$m->date->format('Y-m-d H:i')}, Status: {$m->status}, Questions: {$questions}\n";
}

// Check all recent matches (regardless of status)
echo "\nAll matches (últimos 5 más recientes, ANY status):\n";
$all = FootballMatch::orderByDesc('date')->limit(5)->get();
foreach($all as $m) {
    $questions = $m->questions()->count();
    echo "  Match {$m->id}: {$m->date->format('Y-m-d H:i')}, Status: {$m->status}, Questions: {$questions}\n";
}

// Check 15 days back
echo "\nMatches from 15 days ago:\n";
$window = now()->subDays(15);
echo "Window start: " . $window->format('Y-m-d H:i') . "\n";
$inWindow = FootballMatch::where('date', '>=', $window)
    ->whereIn('status', ['Match Finished', 'FINISHED', 'Finished'])
    ->count();
echo "Finished matches in window: $inWindow\n";

$allInWindow = FootballMatch::where('date', '>=', $window)->count();
echo "All matches in window (any status): $allInWindow\n";

// Check date distribution
echo "\n\nDate distribution (all matches):\n";
$dates = FootballMatch::selectRaw('DATE(date) as date, COUNT(*) as cnt, GROUP_CONCAT(DISTINCT status) as statuses')
    ->groupBy('date')
    ->orderByDesc('date')
    ->limit(10)
    ->get();

foreach($dates as $d) {
    echo "  {$d->date}: {$d->cnt} matches | Statuses: {$d->statuses}\n";
}
