<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\GeminiService;
use Carbon\Carbon;

echo "\n════════════════════════════════════════════════════════════════\n";
echo "🧪 PRUEBA: Buscar Valencia vs Elche (10 enero 2026)\n";
echo "════════════════════════════════════════════════════════════════\n\n";

try {
    $service = app(GeminiService::class);

    echo "🔍 Obteniendo fixtures de Gemini (gemini-3-flash-preview)...\n";
    $start_time = time();

    $fixtures = $service->getFixtures('La Liga', forceRefresh: true);

    $elapsed = time() - $start_time;
    echo "✅ Respuesta recibida en " . $elapsed . " segundos\n\n";

    if (!$fixtures || !isset($fixtures['matches'])) {
        echo "❌ No se obtuvieron partidos\n";
        exit(1);
    }

    echo "📊 Total de partidos obtenidos: " . count($fixtures['matches']) . "\n\n";

    // Mostrar todos los partidos para análisis
    echo "════════════════════════════════════════════════════════════════\n";
    echo "📋 TODOS LOS PARTIDOS OBTENIDOS:\n";
    echo "════════════════════════════════════════════════════════════════\n\n";

    foreach ($fixtures['matches'] as $idx => $match) {
        $date = Carbon::parse($match['date']);
        echo ($idx + 1) . ". " . $match['home_team'] . " vs " . $match['away_team'];
        echo " - " . $date->format('d/m/Y H:i') . "\n";
    }

    // Buscar Valencia vs Elche específicamente
    echo "\n";
    echo "════════════════════════════════════════════════════════════════\n";
    echo "🔍 BÚSQUEDA: Valencia vs Elche (10 enero)\n";
    echo "════════════════════════════════════════════════════════════════\n\n";

    $encontrado = false;
    foreach ($fixtures['matches'] as $match) {
        $home = strtolower($match['home_team']);
        $away = strtolower($match['away_team']);

        $home_match = (strpos($home, 'valencia') !== false);
        $away_match = (strpos($away, 'elche') !== false);

        if (($home_match && $away_match) || (strpos($home, 'elche') !== false && strpos($away, 'valencia') !== false)) {
            echo "✓ ENCONTRADO: " . $match['home_team'] . " vs " . $match['away_team'] . "\n";
            echo "  Fecha: " . $match['date'] . "\n";
            echo "  Liga: " . ($match['league'] ?? 'N/A') . "\n";
            echo "  Estadio: " . ($match['stadium'] ?? 'N/A') . "\n";
            $encontrado = true;
        }
    }

    if (!$encontrado) {
        echo "❌ Valencia vs Elche NO ENCONTRADO\n\n";
        echo "Partidos que sí contienen Valencia o Elche:\n";
        foreach ($fixtures['matches'] as $match) {
            if (stripos($match['home_team'], 'valencia') !== false ||
                stripos($match['away_team'], 'valencia') !== false ||
                stripos($match['home_team'], 'elche') !== false ||
                stripos($match['away_team'], 'elche') !== false) {
                echo "  • " . $match['home_team'] . " vs " . $match['away_team'] . " (" . $match['date'] . ")\n";
            }
        }
    }

    echo "\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
