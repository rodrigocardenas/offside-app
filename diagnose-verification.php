<?php

/**
 * Diagnóstico de estado de verificación
 * Ejecutar con: php artisan tinker < diagnose-verification.php
 */

use App\Models\Question;
use App\Models\FootballMatch;
use DB;

// Matches del 24-30 de abril
$matches = [916, 918, 917, 2023, 2022, 1294, 1948, 1950, 1298, 1297, 1957, 1303, 1951, 930, 925];

echo "\n" . str_repeat("=", 60);
echo "\n🔍 DIAGNÓSTICO DE VERIFICACIÓN\n";
echo str_repeat("=", 60) . "\n\n";

// Revisar estadísticas generales
$stats = DB::table('questions')
    ->whereIn('match_id', $matches)
    ->selectRaw('COUNT(*) as total, 
                 SUM(CASE WHEN result_verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified,
                 SUM(CASE WHEN result_verified_at IS NULL THEN 1 ELSE 0 END) as unverified')
    ->first();

echo "📊 ESTADÍSTICAS GENERALES (" . count($matches) . " matches):\n";
echo "   Total Preguntas: " . $stats->total . "\n";
echo "   ✅ Verificadas: " . $stats->verified . "\n";
echo "   ⏳ Sin Verificar: " . $stats->unverified . "\n";
echo "   Porcentaje: " . round(($stats->verified / $stats->total) * 100, 1) . "% completado\n\n";

// Revisar match 916 específicamente
echo "🔎 DETALLES - MATCH #916 (Espanyol vs Levante):\n";
$m916 = FootballMatch::find(916);
if($m916) {
    echo "   Status: " . $m916->status . "\n";
    echo "   Resultado: " . $m916->home_team_score . "-" . $m916->away_team_score . "\n";
}

$q916 = DB::table('questions')->where('match_id', 916)->get();
foreach($q916 as $q) {
    echo "\n   Q#{$q->id}: " . substr($q->title, 0, 50) . "...\n";
    echo "      Verificada: " . ($q->result_verified_at ? "✅ " . $q->result_verified_at : "⏳ NO") . "\n";
    
    $correct = DB::table('question_options')
        ->where('question_id', $q->id)
        ->where('is_correct', 1)
        ->first();
    echo "      Opción Correcta: " . ($correct ? "✅ " . $correct->text : "❌ NO ENCONTRADA") . "\n";
    
    $opts = DB::table('question_options')->where('question_id', $q->id)->get();
    echo "      Opciones (" . $opts->count() . "):\n";
    foreach($opts as $opt) {
        echo "         - " . $opt->text . " [is_correct=" . ($opt->is_correct ? "1" : "0") . "]\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
