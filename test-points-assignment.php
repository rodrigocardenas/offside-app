<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Answer;
use App\Models\Question;
use App\Models\FootballMatch;

echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║   REPORTE: Estado de Asignación de Puntos                        ║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n\n";

// Get verified questions
$verifiedQuestions = Question::whereNotNull('result_verified_at')
    ->with('answers')
    ->get();

echo "Total preguntas verificadas: " . $verifiedQuestions->count() . "\n\n";

// Aggregate stats
$totalAnswers = $verifiedQuestions->sum(function($q) {
    return $q->answers->count();
});

$totalPointsAssigned = $verifiedQuestions->sum(function($q) {
    return $q->answers->sum('points_earned');
});

$correctAnswers = Answer::where('is_correct', 1)->count();
$incorrectAnswers = Answer::where('is_correct', 0)->count();

echo "═══════════════════════════════════════════════════════════════════\n";
echo "📊 ESTADÍSTICAS GLOBALES\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "✅ Respuestas correctas: $correctAnswers\n";
echo "❌ Respuestas incorrectas: $incorrectAnswers\n";
echo "📝 Total respuestas: $totalAnswers\n";
echo "🎯 Puntos totales asignados: $totalPointsAssigned\n";
echo "💰 Promedio puntos por pregunta verificada: " . ($verifiedQuestions->count() > 0 ? round($totalPointsAssigned / $verifiedQuestions->count(), 2) : 0) . "\n";

// Show sample questions
echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "📋 MUESTRA DE 10 PREGUNTAS VERIFICADAS\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$verifiedQuestions->take(10)->each(function($q) {
    $correctCount = $q->answers->where('is_correct', 1)->count();
    $pointsTotal = $q->answers->sum('points_earned');
    $questText = substr($q->text, 0, 60) . (strlen($q->text) > 60 ? '...' : '');

    echo "Q{$q->id}: \"$questText\"\n";
    echo "  Respuestas: {$q->answers->count()} | Correctas: $correctCount | Puntos totales: $pointsTotal\n";
    echo "  Verificada: {$q->result_verified_at}\n\n";
});

// Show user points distribution
echo "═══════════════════════════════════════════════════════════════════\n";
echo "👥 TOP 5 USUARIOS POR PUNTOS\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$topUsers = \DB::table('answers')
    ->select('user_id', \DB::raw('COUNT(*) as answer_count'), \DB::raw('SUM(points_earned) as total_points'))
    ->groupBy('user_id')
    ->orderBy('total_points', 'desc')
    ->limit(5)
    ->get();

$topUsers->each(function($u, $idx) {
    echo ($idx+1) . ". User {$u->user_id}: {$u->total_points} puntos (respuestas: {$u->answer_count})\n";
});

echo "\n✅ Si ves números aquí en 'Puntos totales asignados' > 0, significa que SÍ se están asignando puntos.\n";
echo "❌ Si todos los números son 0, significa que hay un problema con la asignación.\n";
