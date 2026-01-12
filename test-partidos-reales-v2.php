<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\GeminiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

echo "\n════════════════════════════════════════════════════════════════\n";
echo "🔍 OBTENER PARTIDOS REALES - Gemini 3 Pro Preview\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Primero verificar si el caché tiene datos recientes
$cache_key = 'gemini_fixtures_La Liga';
$cached = Cache::get($cache_key);

if ($cached) {
    echo "✅ Datos encontrados en caché local (24 horas)\n";
    echo "   Usaremos estos datos para evitar rate limiting\n\n";
    $fixtures = $cached;
} else {
    echo "ℹ️  Caché vacío, obteniendo fixtures de Gemini...\n";
    echo "   Esto puede tomar varios minutos por rate limiting\n";
    echo "   Esperando 120 segundos antes de intentar...\n\n";

    sleep(120);

    try {
        $service = app(GeminiService::class);
        $fixtures = $service->getFixtures('La Liga', forceRefresh: true);
    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n\n";
        echo "💡 RECOMENDACIÓN: Los límites de Gemini API son estrictos\n";
        echo "   Por favor intenta de nuevo en 5-10 minutos\n";
        exit(1);
    }
}

if (!$fixtures || !isset($fixtures['matches']) || empty($fixtures['matches'])) {
    echo "❌ Sin partidos disponibles\n";
    exit(1);
}

echo "✅ Obtenidos " . count($fixtures['matches']) . " partidos\n\n";

// Mostrar todos los partidos organizados por fecha
echo "════════════════════════════════════════════════════════════════\n";
echo "📋 PARTIDOS OBTENIDOS POR FECHA:\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$partidos_por_fecha = [];
foreach ($fixtures['matches'] as $match) {
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
    echo "📅 " . Carbon::parse($fecha)->translatedFormat('l, d \\d\\e F') . ":\n";
    foreach ($matches as $match) {
        $time = Carbon::parse($match['date'])->format('H:i');
        echo "   " . str_pad($contador, 2) . ". " . $match['home_team'] . " vs " . $match['away_team'];
        echo " (" . $time . ")\n";
        $contador++;
    }
    echo "\n";
}

// Búsqueda específica
echo "════════════════════════════════════════════════════════════════\n";
echo "🎯 BÚSQUEDA: Girona vs Osasuna (10 enero 2026)\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$encontrado = false;
foreach ($fixtures['matches'] as $match) {
    $home = strtolower(trim($match['home_team']));
    $away = strtolower(trim($match['away_team']));
    $date = Carbon::parse($match['date']);

    // Búsqueda flexible
    $home_match = (strpos($home, 'girona') !== false);
    $away_match = (strpos($away, 'osasuna') !== false);
    $away_match_rev = (strpos($away, 'girona') !== false && strpos($home, 'osasuna') !== false);

    if (($home_match && $away_match || $away_match_rev) && $date->day == 10 && $date->month == 1) {
        $encontrado = true;
        echo "✅ ENCONTRADO:\n\n";
        echo "   Partido: " . $match['home_team'] . " vs " . $match['away_team'] . "\n";
        echo "   Fecha:   " . $date->format('d de F de Y') . "\n";
        echo "   Hora:    " . $date->format('H:i') . "\n";
        echo "   Liga:    " . ($match['league'] ?? 'La Liga') . "\n";
        echo "   Estadio: " . ($match['stadium'] ?? 'N/A') . "\n";
        break;
    }
}

if (!$encontrado) {
    echo "❌ NO ENCONTRADO en 10 de enero\n\n";

    echo "🔍 Buscando Girona y Osasuna en TODAS las fechas:\n";
    $found_any = false;
    foreach ($fixtures['matches'] as $match) {
        $home = strtolower(trim($match['home_team']));
        $away = strtolower(trim($match['away_team']));

        if ((strpos($home, 'girona') !== false || strpos($away, 'girona') !== false) &&
            (strpos($home, 'osasuna') !== false || strpos($away, 'osasuna') !== false)) {

            $date = Carbon::parse($match['date']);
            echo "   • " . $match['home_team'] . " vs " . $match['away_team'] . " (" . $date->format('d/m/Y H:i') . ")\n";
            $found_any = true;
        }
    }

    if (!$found_any) {
        echo "   ⚠️  Girona y Osasuna NO aparecen juntos en ningún partido\n";
    }
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "⚠️  VALIDACIÓN:\n";
echo "════════════════════════════════════════════════════════════════\n\n";

if ($encontrado) {
    echo "✅ DATOS VERIFICADOS COMO REALES\n";
    echo "   Girona vs Osasuna está presente en los resultados\n";
} else {
    echo "❌ DATOS CUESTIONABLES\n";
    echo "   Girona vs Osasuna NO aparece en los resultados\n";
    echo "   Posibles causas:\n";
    echo "   • Gemini retorna datos ficticios o incorrectos\n";
    echo "   • El partido no existe en La Liga para esa fecha\n";
    echo "   • Los nombres de equipos son diferentes\n";
}

echo "\n";
