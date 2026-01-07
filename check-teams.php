<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n🔍 EQUIPOS DISPONIBLES EN BASE DE DATOS:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$teams = DB::table('teams')
    ->select('id', 'name', 'external_id')
    ->where('league_id', 1) // Assuming La Liga is league_id 1
    ->orWhereNull('league_id')
    ->orderBy('name')
    ->get();

echo "Total de equipos: " . $teams->count() . "\n\n";

if ($teams->count() > 0) {
    echo "Equipos encontrados:\n";
    foreach ($teams as $team) {
        echo "• " . ($team->name ?: '[VACÍO]') . " (ID: " . $team->id . ")\n";
    }
} else {
    echo "No hay equipos en la base de datos\n";
}

// Now let's check what foreign key issues we have
echo "\n\n🔴 PARTIDOS CON PROBLEMAS DE INTEGRIDAD REFERENCIAL:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Partidos donde los IDs de equipos no existen
$problemMatches = DB::table('football_matches AS fm')
    ->leftJoin('teams AS t1', 'fm.home_team_id', '=', 't1.id')
    ->leftJoin('teams AS t2', 'fm.away_team_id', '=', 't2.id')
    ->whereNull('t1.id')
    ->orWhereNull('t2.id')
    ->select('fm.id', 'fm.home_team_id', 'fm.away_team_id', 't1.name AS home', 't2.name AS away')
    ->limit(5)
    ->get();

echo "Partidos con IDs de equipos inválidos: " . $problemMatches->count() . "\n\n";

foreach ($problemMatches as $match) {
    echo "• Match ID " . $match->id . ": home_team_id=" . $match->home_team_id . " away_team_id=" . $match->away_team_id . "\n";
}
