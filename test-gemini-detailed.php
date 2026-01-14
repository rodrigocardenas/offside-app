<?php
/**
 * Test para validar que getDetailedMatchData está funcionando correctamente
 */

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\GeminiService;
use App\Models\FootballMatch;

echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║ TEST: GeminiService::getDetailedMatchData()               ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";

$geminiService = app(GeminiService::class);

// Usar un partido reciente que no haya sido procesado
$testMatches = [
    [
        'home' => 'Real Madrid',
        'away' => 'Barcelona',
        'date' => '2026-01-14',
        'league' => 'La Liga'
    ],
    [
        'home' => 'Manchester City',
        'away' => 'Liverpool',
        'date' => '2026-01-14',
        'league' => 'Premier League'
    ]
];

foreach ($testMatches as $match) {
    echo "\n🔍 TEST: {$match['home']} vs {$match['away']}\n";
    echo "   Date: {$match['date']}\n";
    echo "   League: {$match['league']}\n";

    echo "\n   📡 Llamando getDetailedMatchData()...\n";

    try {
        $result = $geminiService->getDetailedMatchData(
            $match['home'],
            $match['away'],
            $match['date'],
            $match['league'],
            true // Force refresh
        );

        if ($result) {
            echo "   ✅ Resultado obtenido!\n";
            echo "   ├─ home_goals: " . ($result['home_goals'] ?? 'N/A') . "\n";
            echo "   ├─ away_goals: " . ($result['away_goals'] ?? 'N/A') . "\n";
            echo "   ├─ events: " . count($result['events'] ?? []) . " eventos\n";
            echo "   ├─ first_goal_scorer: " . ($result['first_goal_scorer'] ?? 'N/A') . "\n";
            echo "   ├─ total_yellow_cards: " . ($result['total_yellow_cards'] ?? 0) . "\n";
            echo "   └─ home_possession: " . ($result['home_possession'] ?? 'N/A') . "%\n";

            // Mostrar eventos si existen
            if (!empty($result['events'])) {
                echo "\n   📋 Eventos registrados:\n";
                foreach ($result['events'] as $idx => $event) {
                    $min = $event['minute'] ?? '?';
                    $type = $event['type'] ?? 'UNKNOWN';
                    $team = $event['team'] ?? 'UNKNOWN';
                    $player = $event['player'] ?? 'Unknown';
                    echo "      {$idx}) Min {$min} - {$type} ({$team}): {$player}\n";
                }
            }
        } else {
            echo "   ❌ Retornó NULL\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    }

    echo "\n" . str_repeat("─", 70) . "\n";
}

echo "\n✅ Test completado\n";
echo "\nRevisa storage/logs/laravel.log para ver los logs detallados\n";

?>
