<?php
chdir(__DIR__);
require './bootstrap/autoload.php';
$app = require_once './bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\FootballMatch;
use App\Services\FootballService;

$service = app(FootballService::class);

$matches = FootballMatch::whereIn('status', ['Not Started', 'Scheduled', 'In Play', 'Match Finished'])
    ->where('date', '<=', now()->subHours(2))
    ->where('date', '>=', now()->subDays(30))
    ->where('external_id', '!=', '')
    ->where('external_id', '!=', null)
    ->orderBy('date', 'desc')
    ->get();

echo "TOTAL MATCHES: " . count($matches) . "\n\n";

foreach ($matches as $m) {
    echo "ID: {$m->id} | {$m->home_team} vs {$m->away_team}\n";
    echo "  External ID: {$m->external_id}\n";
    echo "  Current Score: {$m->score} | Status: {$m->status}\n";
    echo "  Date: {$m->date}\n";
    
    // Intentar obtener fixture
    $fixture = $service->obtenerFixtureDirecto($m->external_id);
    
    if ($fixture) {
        $homeScore = $fixture['goals']['home'] ?? null;
        $awayScore = $fixture['goals']['away'] ?? null;
        echo "  ✓ API Result: {$homeScore}-{$awayScore}\n";
    } else {
        echo "  ✗ FAILED to get fixture\n";
    }
    echo "\n";
}
