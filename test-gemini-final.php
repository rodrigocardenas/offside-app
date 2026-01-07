<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Carbon\Carbon;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "🧪 PRUEBA FINAL: Verificar que Gemini obtiene los partidos reales\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "📅 Contexto:\n";
echo "  • Fecha actual: " . Carbon::now()->format('d de F de Y H:i') . "\n";
echo "  • Competición: La Liga - Jornada 19\n";
echo "  • Período a consultar: 8-10 enero 2026\n\n";

echo "🎯 Partidos específicos a validar:\n";
echo "  ✓ Real Sociedad vs Getafe (8 enero 2026)\n";
echo "  ✓ Villarreal vs Oviedo (10 enero 2026)\n\n";

echo "🔄 Llamando a GeminiService::getFixtures()...\n";
echo "   (Puede tomar 2-3 minutos si hay límite de velocidad)\n\n";

try {
    $service = app(GeminiService::class);

    // Limpiar caché para obtener datos frescos
    echo "⏳ Limpiando caché local...\n";
    \Illuminate\Support\Facades\Cache::forget('gemini_fixtures_La Liga');

    echo "🔍 Obteniendo fixtures de Gemini...\n";
    $start_time = time();

    $fixtures = $service->getFixtures('La Liga', forceRefresh: true);

    $elapsed = time() - $start_time;
    echo "✅ Respuesta recibida en " . $elapsed . " segundos\n\n";

    if (!$fixtures || !isset($fixtures['matches']) || empty($fixtures['matches'])) {
        echo "❌ Error: No se obtuvieron partidos\n";
        exit(1);
    }

    echo "📊 Partidos obtenidos: " . count($fixtures['matches']) . "\n\n";

    // Búsqueda de partidos específicos
    $partidos_buscados = [
        ['home' => 'Real Sociedad', 'away' => 'Getafe', 'label' => 'Real Sociedad vs Getafe (8 enero)'],
        ['home' => 'Villarreal', 'away' => 'Oviedo', 'label' => 'Villarreal vs Oviedo (10 enero)'],
    ];

    echo "════════════════════════════════════════════════════════════════\n";
    echo "📋 RESULTADOS DE BÚSQUEDA\n";
    echo "════════════════════════════════════════════════════════════════\n\n";

    $encontrados = 0;
    $no_encontrados = [];

    foreach ($partidos_buscados as $buscado) {
        $encontrado = false;

        foreach ($fixtures['matches'] as $match) {
            $home_match = strtolower(trim($match['home_team'] ?? ''));
            $away_match = strtolower(trim($match['away_team'] ?? ''));
            $home_buscado = strtolower(trim($buscado['home']));
            $away_buscado = strtolower(trim($buscado['away']));

            // Búsqueda flexible
            $home_coincide = strpos($home_match, $home_buscado) !== false || strpos($home_buscado, $home_match) !== false;
            $away_coincide = strpos($away_match, $away_buscado) !== false || strpos($away_buscado, $away_match) !== false;

            if ($home_coincide && $away_coincide) {
                $encontrados++;
                echo "✓ " . $buscado['label'] . "\n";
                echo "  Encontrado como: " . $match['home_team'] . " vs " . $match['away_team'] . "\n";
                echo "  Fecha: " . $match['date'] . "\n";
                echo "  Estadio: " . ($match['stadium'] ?? 'N/A') . "\n";
                echo "  Liga: " . ($match['league'] ?? 'N/A') . "\n\n";
                $encontrado = true;
                break;
            }
        }

        if (!$encontrado) {
            $no_encontrados[] = $buscado['label'];
        }
    }

    if (!empty($no_encontrados)) {
        echo "❌ PARTIDOS NO ENCONTRADOS:\n";
        foreach ($no_encontrados as $not_found) {
            echo "  ✗ " . $not_found . "\n";
        }
        echo "\n";
    }

    // Mostrar muestra de otros partidos encontrados
    echo "════════════════════════════════════════════════════════════════\n";
    echo "📅 MUESTRA DE OTROS PARTIDOS OBTENIDOS DE GEMINI:\n";
    echo "════════════════════════════════════════════════════════════════\n\n";

    $muestra = array_slice($fixtures['matches'], 0, 8);
    foreach ($muestra as $match) {
        echo "• " . $match['home_team'] . " vs " . $match['away_team'];
        echo " (" . $match['date'] . ")\n";
    }

    if (count($fixtures['matches']) > 8) {
        echo "... y " . (count($fixtures['matches']) - 8) . " más\n";
    }

    // Resumen final
    echo "\n";
    echo "════════════════════════════════════════════════════════════════\n";
    echo "📈 RESUMEN FINAL\n";
    echo "════════════════════════════════════════════════════════════════\n\n";

    echo "Total de partidos obtenidos: " . count($fixtures['matches']) . "\n";
    echo "Partidos buscados encontrados: " . $encontrados . "/" . count($partidos_buscados) . "\n";

    if ($encontrados == count($partidos_buscados)) {
        echo "\n✅ ÉXITO: Gemini obtiene correctamente los partidos reales\n";
        echo "   Los datos de la API son precisos para Jornada 19 (enero 2026)\n";
    } else {
        echo "\n⚠️  PARCIAL: Algunos partidos no se encontraron\n";
        echo "   Verificar nombres de equipos o disponibilidad en Gemini\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
