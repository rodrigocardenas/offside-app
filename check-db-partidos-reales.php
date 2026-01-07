<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n════════════════════════════════════════════════════════════════\n";
echo "🔍 VERIFICACIÓN DE PARTIDOS EN BASE DE DATOS\n";
echo "════════════════════════════════════════════════════════════════\n\n";

// Buscar Girona vs Osasuna en cualquier fecha de enero
echo "🎯 Buscando Girona vs Osasuna en enero 2026...\n\n";

$matches = DB::table('football_matches')
    ->where(function($q) {
        $q->where('home_team', 'like', '%Girona%')
          ->where('away_team', 'like', '%Osasuna%');
    })
    ->orWhere(function($q) {
        $q->where('home_team', 'like', '%Osasuna%')
          ->where('away_team', 'like', '%Girona%');
    })
    ->whereBetween('date', ['2026-01-01', '2026-01-31'])
    ->get();

if ($matches->count() > 0) {
    echo "✅ ENCONTRADO: Girona vs Osasuna\n\n";
    foreach ($matches as $match) {
        $date = Carbon::parse($match->date);
        echo "   Partido: " . $match->home_team . " vs " . $match->away_team . "\n";
        echo "   Fecha:   " . $date->format('d de F de Y') . "\n";
        echo "   Hora:    " . $date->format('H:i') . "\n";
        echo "   Liga:    " . $match->league . "\n";
        echo "   Estado:  " . $match->status . "\n";
        echo "\n";
    }
} else {
    echo "❌ NO ENCONTRADO: Girona vs Osasuna en enero 2026\n\n";
}

// Mostrar todos los partidos de enero 2026 para el 10
echo "════════════════════════════════════════════════════════════════\n";
echo "📋 TODOS LOS PARTIDOS DE 10 DE ENERO 2026:\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$matches_10_enero = DB::table('football_matches')
    ->whereDate('date', '2026-01-10')
    ->orderBy('date')
    ->get();

if ($matches_10_enero->count() > 0) {
    foreach ($matches_10_enero as $idx => $match) {
        $date = Carbon::parse($match->date);
        echo ($idx + 1) . ". " . $match->home_team . " vs " . $match->away_team;
        echo " (" . $date->format('H:i') . ")\n";
    }
    echo "\nTotal: " . $matches_10_enero->count() . " partidos\n";
} else {
    echo "No hay partidos registrados para el 10 de enero\n";
}

// Contar total en enero
echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "📊 RESUMEN DE ENERO 2026:\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$total_enero = DB::table('football_matches')
    ->whereBetween('date', ['2026-01-01', '2026-01-31'])
    ->count();

echo "Total de partidos en enero: " . $total_enero . "\n";

// Agrupar por fecha
echo "\nPartidos por fecha:\n";
$por_fecha = DB::table('football_matches')
    ->whereBetween('date', ['2026-01-01', '2026-01-31'])
    ->selectRaw('DATE(date) as fecha, COUNT(*) as cantidad')
    ->groupBy('fecha')
    ->orderBy('fecha')
    ->get();

foreach ($por_fecha as $row) {
    $date = Carbon::parse($row->fecha);
    echo "  " . $date->format('d M') . ": " . $row->cantidad . " partidos\n";
}

echo "\n";
