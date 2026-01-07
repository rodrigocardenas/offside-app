<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Carbon\Carbon;
use App\Services\GeminiService;

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "🧪 PRUEBA: Verificar Partidos Reales de La Liga (Jornada 19)\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "📅 Fecha actual: " . Carbon::now()->format('d de F de Y') . "\n";
echo "🏆 Competición: La Liga - Jornada 19\n\n";

echo "Buscando los siguientes partidos:\n";
echo "  • 9 de enero 2026:  Getafe vs Real Sociedad\n";
echo "  • 10 de enero 2026: Villarreal vs Oviedo\n\n";

try {
    $service = app(GeminiService::class);
    
    echo "🔄 Obteniendo fixtures de Gemini (usando caché si está disponible)...\n";
    // Primero intentar con caché
    $fixtures = $service->getFixtures('La Liga', forceRefresh: false);
    
    if (!$fixtures) {
        echo "⏳ Esperando 35 segundos antes de reintentar (límite de velocidad de Gemini)...\n";
        sleep(35);
        $fixtures = $service->getFixtures('La Liga', forceRefresh: true);
    }
    
    if (!$fixtures || !isset($fixtures['matches']) || empty($fixtures['matches'])) {
        echo "❌ No se obtuvieron partidos\n";
        exit(1);
    }
    
    echo "✅ Obtenidos " . count($fixtures['matches']) . " partidos\n\n";
    
    // Búsqueda de partidos específicos
    $partidos_buscados = [
        ['home' => 'Getafe', 'away' => 'Real Sociedad', 'date' => '2026-01-09', 'label' => 'Getafe vs Real Sociedad (9 enero)'],
        ['home' => 'Villarreal', 'away' => 'Oviedo', 'date' => '2026-01-10', 'label' => 'Villarreal vs Oviedo (10 enero)'],
    ];
    
    echo "📊 BÚSQUEDA DE PARTIDOS ESPECÍFICOS:\n";
    echo "════════════════════════════════════════════════════════════════\n\n";
    
    $encontrados = [];
    $no_encontrados = [];
    
    foreach ($partidos_buscados as $buscado) {
        $encontrado = false;
        
        foreach ($fixtures['matches'] as $match) {
            $home_match = strtolower(trim($match['home_team'] ?? ''));
            $away_match = strtolower(trim($match['away_team'] ?? ''));
            $home_buscado = strtolower(trim($buscado['home']));
            $away_buscado = strtolower(trim($buscado['away']));
            
            // Búsqueda flexible (puede haber variaciones en nombres)
            $home_coincide = strpos($home_match, $home_buscado) !== false || strpos($home_buscado, $home_match) !== false;
            $away_coincide = strpos($away_match, $away_buscado) !== false || strpos($away_buscado, $away_match) !== false;
            
            if ($home_coincide && $away_coincide) {
                $encontrados[] = [
                    'label' => $buscado['label'],
                    'found' => $match['home_team'] . ' vs ' . $match['away_team'],
                    'date' => $match['date'],
                    'status' => $match['status'] ?? 'unknown',
                    'stadium' => $match['stadium'] ?? 'N/A'
                ];
                $encontrado = true;
                break;
            }
        }
        
        if (!$encontrado) {
            $no_encontrados[] = $buscado['label'];
        }
    }
    
    // Mostrar resultados
    if (!empty($encontrados)) {
        echo "✅ PARTIDOS ENCONTRADOS:\n";
        echo "─────────────────────────────────────────────────────────────\n";
        foreach ($encontrados as $party) {
            echo "\n✓ " . $party['label'] . "\n";
            echo "  Encontrado: " . $party['found'] . "\n";
            echo "  Fecha:      " . $party['date'] . "\n";
            echo "  Estado:     " . $party['status'] . "\n";
            echo "  Estadio:    " . $party['stadium'] . "\n";
        }
    }
    
    if (!empty($no_encontrados)) {
        echo "\n\n❌ PARTIDOS NO ENCONTRADOS:\n";
        echo "─────────────────────────────────────────────────────────────\n";
        foreach ($no_encontrados as $not_found) {
            echo "✗ " . $not_found . "\n";
        }
    }
    
    echo "\n\n📋 OTROS PARTIDOS ENCONTRADOS EN LA MISMA FECHA:\n";
    echo "────────────────────────────────────────────────────────────────\n";
    
    $por_fecha = [];
    foreach ($fixtures['matches'] as $match) {
        $fecha = substr($match['date'], 0, 10);
        if (!isset($por_fecha[$fecha])) {
            $por_fecha[$fecha] = [];
        }
        $por_fecha[$fecha][] = $match;
    }
    
    // Mostrar partidos para el 9 y 10 de enero
    foreach (['2026-01-09', '2026-01-10'] as $date) {
        if (isset($por_fecha[$date])) {
            echo "\n📅 " . Carbon::createFromFormat('Y-m-d', $date)->translatedFormat('l, d \\d\\e F \\d\\e Y') . ":\n";
            foreach ($por_fecha[$date] as $match) {
                echo "   • " . $match['home_team'] . " vs " . $match['away_team'];
                echo " (" . substr($match['date'], 11, 5) . ")\n";
            }
        }
    }
    
    // Estadísticas
    echo "\n\n📈 ESTADÍSTICAS:\n";
    echo "────────────────────────────────────────────────────────────────\n";
    echo "Total de partidos: " . count($fixtures['matches']) . "\n";
    echo "Partidos buscados: " . count($partidos_buscados) . "\n";
    echo "Partidos encontrados: " . count($encontrados) . "\n";
    echo "Partidos no encontrados: " . count($no_encontrados) . "\n";
    
    if (count($encontrados) == count($partidos_buscados)) {
        echo "\n✅ PRUEBA EXITOSA: Todos los partidos reales fueron encontrados correctamente\n";
    } else {
        echo "\n⚠️  PRUEBA PARCIAL: Algunos partidos no se encontraron\n";
        echo "   Posibles causas:\n";
        echo "   - Gemini usa nombres de equipos diferentes\n";
        echo "   - Los partidos no están en el rango de los próximos 7 días\n";
        echo "   - La API no tiene información completa\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
