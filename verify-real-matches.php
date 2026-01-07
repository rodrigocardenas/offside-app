<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n════════════════════════════════════════════════════════════════\n";
echo "✅ VERIFICACIÓN: PARTIDOS REALES EN BASE DE DATOS\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Búsqueda de los partidos específicos mencionados
$partidos_buscados = [
    ['home' => 'Getafe', 'away' => 'Real Sociedad', 'date' => '2026-01-09'],
    ['home' => 'Real Sociedad', 'away' => 'Getafe', 'date' => '2026-01-08'],
    ['home' => 'Villarreal', 'away' => 'Oviedo', 'date' => '2026-01-10'],
];

echo "🔍 BÚSQUEDA DE PARTIDOS ESPECÍFICOS:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

foreach ($partidos_buscados as $buscado) {
    $match = DB::table('football_matches')
        ->where('home_team', $buscado['home'])
        ->where('away_team', $buscado['away'])
        ->whereDate('date', $buscado['date'])
        ->first();
    
    if ($match) {
        $date = Carbon::parse($match->date);
        echo "✓ " . $buscado['home'] . " vs " . $buscado['away'] . "\n";
        echo "  Fecha: " . $date->translatedFormat('l, d \\d\\e F \\d\\e Y') . " a las " . $date->format('H:i') . "\n";
        echo "  Estadio: " . ($match->stadium ?: 'N/A') . "\n";
        echo "  Estado: " . $match->status . "\n\n";
    } else {
        echo "✗ " . $buscado['home'] . " vs " . $buscado['away'] . " - NO ENCONTRADO\n\n";
    }
}

echo "📅 TODOS LOS PARTIDOS DE JORNADA 19 (8-10 ENERO):\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$matches = DB::table('football_matches')
    ->whereBetween('date', ['2026-01-08 00:00:00', '2026-01-10 23:59:59'])
    ->orderBy('date')
    ->get();

foreach ($matches as $match) {
    $date = Carbon::parse($match->date);
    echo "• " . $match->home_team . " vs " . $match->away_team;
    echo " - " . $date->translatedFormat('l, d M Y H:i') . "\n";
}

echo "\n📊 RESUMEN:\n";
echo "Total de partidos: " . $matches->count() . "\n";
