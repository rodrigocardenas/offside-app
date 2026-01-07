<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\GeminiService;
use Carbon\Carbon;

echo "\n════════════════════════════════════════════════════════════════\n";
echo "🔍 VALIDACIÓN DE PARTIDOS REALES - Gemini 3 Pro Preview\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "📋 Partidos reales conocidos de La Liga (10 enero 2026):\n";
echo "  • Girona vs Osasuna ← Este DEBE estar en los resultados\n";
echo "  • (otros partidos reales de esa fecha)\n\n";

try {
    $service = app(GeminiService::class);

    echo "🔄 Limpiando caché...\n";
    \Illuminate\Support\Facades\Cache::forget('gemini_fixtures_La Liga');

    echo "🔍 Obteniendo fixtures de Gemini (gemini-3-pro-preview)...\n";
    $start_time = time();

    $fixtures = $service->getFixtures('La Liga', forceRefresh: true);

    $elapsed = time() - $start_time;
    echo "✅ Respuesta recibida en " . $elapsed . " segundos\n\n";

    if (!$fixtures || !isset($fixtures['matches']) || empty($fixtures['matches'])) {
        echo "❌ Error: No se obtuvieron partidos\n";
        exit(1);
    }

    echo "📊 Total de partidos obtenidos: " . count($fixtures['matches']) . "\n\n";

    // Mostrar todos los partidos
    echo "════════════════════════════════════════════════════════════════\n";
    echo "📋 TODOS LOS PARTIDOS RETORNADOS:\n";
    echo "════════════════════════════════════════════════════════════════\n\n";

    $partidos_por_fecha = [];
    foreach ($fixtures['matches'] as $idx => $match) {
        $date = Carbon::parse($match['date']);
        $fecha_str = $date->format('Y-m-d');

        if (!isset($partidos_por_fecha[$fecha_str])) {
            $partidos_por_fecha[$fecha_str] = [];
        }
        $partidos_por_fecha[$fecha_str][] = $match;
    }

    ksort($partidos_por_fecha);

    $contador = 1;
    foreach ($partidos_por_fecha as $fecha => $matches) {
        echo "📅 " . Carbon::parse($fecha)->translatedFormat('l, d \\d\\e F \\d\\e Y') . ":\n";
        foreach ($matches as $match) {
            $time = Carbon::parse($match['date'])->format('H:i');
            echo "   " . $contador . ". " . $match['home_team'] . " vs " . $match['away_team'];
            echo " (" . $time . ")\n";
            $contador++;
        }
        echo "\n";
    }

    // Buscar Girona vs Osasuna específicamente
    echo "════════════════════════════════════════════════════════════════\n";
    echo "🎯 BÚSQUEDA ESPECÍFICA: Girona vs Osasuna (10 enero)\n";
    echo "════════════════════════════════════════════════════════════════\n\n";

    $encontrado = false;
    $girona_osasuna_items = [];

    foreach ($fixtures['matches'] as $match) {
        $home = strtolower($match['home_team']);
        $away = strtolower($match['away_team']);
        $date = Carbon::parse($match['date']);

        // Buscar cualquier combinación con Girona y Osasuna en la fecha 10
        if ((strpos($home, 'girona') !== false || strpos($away, 'girona') !== false) &&
            (strpos($home, 'osasuna') !== false || strpos($away, 'osasuna') !== false) &&
            $date->day == 10) {

            $encontrado = true;
            $girona_osasuna_items[] = [
                'home_team' => $match['home_team'],
                'away_team' => $match['away_team'],
                'date' => $match['date'],
                'stadium' => $match['stadium'] ?? 'N/A',
                'league' => $match['league'] ?? 'N/A'
            ];
        }
    }

    if ($encontrado) {
        echo "✅ ENCONTRADO: Girona vs Osasuna (10 enero)\n\n";
        foreach ($girona_osasuna_items as $item) {
            $date = Carbon::parse($item['date']);
            echo "  • " . $item['home_team'] . " vs " . $item['away_team'] . "\n";
            echo "    Fecha: " . $date->format('d de F de Y H:i') . "\n";
            echo "    Liga: " . $item['league'] . "\n";
            echo "    Estadio: " . $item['stadium'] . "\n";
        }
    } else {
        echo "❌ NO ENCONTRADO: Girona vs Osasuna en 10 de enero\n\n";

        // Buscar si existe en otras fechas
        echo "🔍 Buscando Girona vs Osasuna en todas las fechas:\n";
        $found_anywhere = false;
        foreach ($fixtures['matches'] as $match) {
            $home = strtolower($match['home_team']);
            $away = strtolower($match['away_team']);

            if ((strpos($home, 'girona') !== false || strpos($away, 'girona') !== false) &&
                (strpos($home, 'osasuna') !== false || strpos($away, 'osasuna') !== false)) {
                $date = Carbon::parse($match['date']);
                echo "  • " . $match['home_team'] . " vs " . $match['away_team'] . " (" . $date->format('Y-m-d H:i') . ")\n";
                $found_anywhere = true;
            }
        }

        if (!$found_anywhere) {
            echo "  ⚠️  Girona vs Osasuna NO APARECE en los resultados\n";
        }
    }

    echo "\n";
    echo "════════════════════════════════════════════════════════════════\n";
    echo "⚠️  VERIFICACIÓN DE INTEGRIDAD:\n";
    echo "════════════════════════════════════════════════════════════════\n\n";

    // Verificar que haya partidos para el 10 de enero
    $partidos_10_enero = array_filter($partidos_por_fecha, function($key) {
        return strpos($key, '2026-01-10') === 0;
    }, ARRAY_FILTER_USE_KEY);

    echo "Partidos para 10 de enero: " . count($partidos_10_enero ?? []) . "\n";
    if (!empty($partidos_10_enero)) {
        foreach ($partidos_10_enero as $matches) {
            echo "  " . count($matches) . " partidos encontrados\n";
        }
    }

    // Análisis de confiabilidad
    echo "\n📊 ANÁLISIS DE RESULTADOS:\n";
    $es_valido = ($encontrado && count($girona_osasuna_items) > 0);
    if ($es_valido) {
        echo "✅ Datos REALES verificados\n";
    } else {
        echo "⚠️  Posibles datos FICTICIOS o inexactos\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
