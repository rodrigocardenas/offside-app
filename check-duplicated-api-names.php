<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Team;

echo "=== API_NAME CON MÚLTIPLES NOMBRES ===\n";

// Buscar api_name que aparecen en múltiples teams
$apiNames = Team::whereNotNull('api_name')
    ->where('api_name', '!=', '')
    ->get()
    ->groupBy('api_name');

$duplicated = $apiNames->filter(function($group) {
    return count($group) > 1;
});

echo "Total api_name duplicados: " . count($duplicated) . "\n\n";

foreach ($duplicated as $apiName => $teams) {
    echo "api_name: '{$apiName}'\n";
    foreach ($teams as $t) {
        echo "  → {$t->name} (ID {$t->id})\n";
    }
    echo "\n";
}
