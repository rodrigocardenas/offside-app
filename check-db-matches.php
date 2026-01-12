<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n📊 PARTIDOS EN BASE DE DATOS (8-11 enero 2026):\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$matches = DB::table('football_matches')
    ->whereBetween('date', ['2026-01-08', '2026-01-11'])
    ->orderBy('date')
    ->get();

if ($matches->isEmpty()) {
    echo "No hay partidos registrados en esas fechas\n";
} else {
    foreach ($matches as $match) {
        $home = DB::table('teams')->where('id', $match->home_team_id)->value('name');
        $away = DB::table('teams')->where('id', $match->away_team_id)->value('name');
        $date = Carbon::parse($match->date);
        echo "• " . $home . " vs " . $away . " - " . $date->format('l, d M Y H:i') . "\n";
    }
}

echo "\nTotal: " . $matches->count() . " partidos\n";

echo "\n\n📅 TODOS LOS PARTIDOS EN ENERO 2026:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$january_matches = DB::table('football_matches')
    ->whereBetween('date', ['2026-01-01', '2026-01-31'])
    ->orderBy('date')
    ->limit(15)
    ->get();

echo "Primeros 15 partidos de enero:\n";
foreach ($january_matches as $match) {
    $home = DB::table('teams')->where('id', $match->home_team_id)->value('name');
    $away = DB::table('teams')->where('id', $match->away_team_id)->value('name');
    $date = Carbon::parse($match->date);
    echo "• " . $home . " vs " . $away . " - " . $date->format('Y-m-d H:i') . "\n";
}

echo "\nTotal de partidos en enero: " . DB::table('football_matches')
    ->whereBetween('date', ['2026-01-01', '2026-01-31'])
    ->count() . "\n";
