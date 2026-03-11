<?php
// Script para inspeccionar Match 2003 y sus preguntas
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\FootballMatch;
use App\Models\Question;

$match = FootballMatch::find(2003);
if (!$match) {
    echo "Match 2003 no encontrado\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════\n";
echo "MATCH 2003\n";
echo "═══════════════════════════════════════════════════════\n";
echo "Equipos: {$match->home_team} vs {$match->away_team}\n";
echo "Resultado: {$match->home_team_score} - {$match->away_team_score}\n";
echo "Fecha: {$match->date}\n";
echo "Status: {$match->status}\n";

$stats = $match->statistics;
if ($stats) {
    echo "\nESTADÍSTICAS:\n";
    if (isset($stats['possession'])) {
        echo "Posesión:\n";
        echo "  - {$match->home_team}: " . ($stats['possession']['home_percentage'] ?? 'N/A') . "%\n";
        echo "  - {$match->away_team}: " . ($stats['possession']['away_percentage'] ?? 'N/A') . "%\n";
    }
}

$questions = Question::where('match_id', 2003)->with('options', 'group')->get();

echo "\n═══════════════════════════════════════════════════════\n";
echo "PREGUNTAS (" . count($questions) . " total)\n";
echo "═══════════════════════════════════════════════════════\n";

foreach ($questions as $q) {
    echo "\n📝 ID: {$q->id} | Grupo: {$q->group->id}\n";
    echo "   Pregunta: " . substr($q->title, 0, 80) . "\n";
    echo "   Verificada: " . ($q->result_verified_at ? 'SÍ' : 'NO') . "\n";
    echo "   Opciones:\n";
    
    foreach ($q->options as $opt) {
        $mark = $opt->is_correct ? '✓' : ' ';
        echo "     [{$mark}] {$opt->id}: {$opt->text}\n";
    }
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "Inspección completada\n";
