<?php
chdir(__DIR__);
require './bootstrap/autoload.php';
$app = require_once './bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

$match = \App\Models\FootballMatch::find(448);
echo "====================================\n";
echo "Partido 448: {$match->home_team} vs {$match->away_team}\n";
echo "Score: {$match->score}\n";
echo "====================================\n";

if ($match->events) {
    echo "\n✓ EVENTOS ENCONTRADOS:\n";
    $events = json_decode($match->events, true);
    echo "Total: " . count($events) . " eventos\n";
    foreach (array_slice($events, 0, 5) as $event) {
        echo "  - {$event['minute']}' | {$event['type']} | {$event['player']} ({$event['team']})\n";
    }
    if (count($events) > 5) {
        echo "  ... y " . (count($events) - 5) . " más\n";
    }
} else {
    echo "\n✗ No hay eventos\n";
}

if ($match->statistics) {
    echo "\n✓ ESTADÍSTICAS ENCONTRADAS:\n";
    $stats = json_decode($match->statistics, true);
    foreach ($stats as $key => $value) {
        if (!is_array($value)) {
            echo "  - $key: $value\n";
        }
    }
} else {
    echo "\n✗ No hay estadísticas\n";
}

echo "\n====================================\n";
