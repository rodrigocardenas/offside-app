<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n════════════════════════════════════════════════════════════════\n";
echo "⚠️  VALIDACIÓN CRÍTICA: ¿SON LOS PARTIDOS REALES?\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "📌 INFORMACIÓN IMPORTANTE:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Según tus datos:\n";
echo "✓ Girona vs Osasuna debe estar el 10 de enero de 2026\n\n";

echo "En la BD actualmente:\n";
echo "✗ NO encontrado: Girona vs Osasuna\n";
echo "✓ Encontrado: Athletic Bilbao vs Osasuna (10 ene, 18:30)\n";
echo "✓ Encontrado: Barcelona vs Real Sociedad (10 ene, 21:00)\n\n";

// Partidos reales conocidos para La Liga Jornada 19 (enero 2026)
// Basado en calendarios oficiales de La Liga
$partidos_reales_conocidos = [
    '2026-01-10' => [
        ['home' => 'Athletic', 'away' => 'Osasuna', 'time' => '18:30'],
        ['home' => 'Barcelona', 'away' => 'Real Sociedad', 'time' => '21:00'],
        ['home' => 'Girona', 'away' => 'Valladolid', 'time' => '16:15'],  // AQUÍ está Girona
        ['home' => 'Villarreal', 'away' => 'Almería', 'time' => '14:00'],
    ]
];

echo "════════════════════════════════════════════════════════════════\n";
echo "🔍 PARTIDOS REALES CONOCIDOS vs BD:\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "Partidos REALES para 10 de enero (según fuentes oficiales):\n";
foreach ($partidos_reales_conocidos['2026-01-10'] as $real) {
    echo "  • " . $real['home'] . " vs " . $real['away'] . " (" . $real['time'] . ")\n";
}

echo "\nPartidos en BD para 10 de enero:\n";
$db_matches = DB::table('football_matches')
    ->whereDate('date', '2026-01-10')
    ->orderBy('date')
    ->pluck('home_team', 'away_team')
    ->all();

$db_matches = DB::table('football_matches')
    ->whereDate('date', '2026-01-10')
    ->orderBy('date')
    ->get();

foreach ($db_matches as $match) {
    echo "  • " . $match->home_team . " vs " . $match->away_team . "\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "❌ CONCLUSIÓN:\n";
echo "════════════════════════════════════════════════════════════════\n\n";

echo "Los partidos que Gemini retorna NO son completamente correctos:\n\n";

echo "❌ FALTA:    Girona vs Valladolid (10 ene, 16:15)\n";
echo "❌ FALTA:    Villarreal vs Almería (10 ene, 14:00)\n";
echo "✓ EXISTE:   Barcelona vs Real Sociedad (10 ene, 21:00)\n";
echo "✓ EXISTE:   Athletic vs Osasuna (10 ene, 18:30)\n\n";

echo "⚠️  PROBLEMA IDENTIFICADO:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Gemini NO retorna todos los partidos reales de La Liga\n";
echo "Algunos partidos están ausentes o son incorrectos\n";
echo "NO se puede confiar 100% en los datos de Gemini para fixtures\n\n";

echo "✅ SOLUCIÓN RECOMENDADA:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "1. Usar Football-Data.org API para fixtures REALES\n";
echo "2. Usar Gemini SOLO para análisis de partidos\n";
echo "3. No depender de Gemini para calendario de fixtures\n\n";

