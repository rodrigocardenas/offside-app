<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FootballMatch;
use App\Models\Question;
use App\Models\User;

$targetDate = '2026-01-31';

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  DIAGNÓSTICO: Matches del 31 de Enero de 2026                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Buscar matches del 31 de enero
$matches = FootballMatch::whereDate('date', $targetDate)
    ->whereIn('status', ['Finished', 'Match Finished', 'FINISHED'])
    ->with(['questions' => function($q) {
        $q->with('answers');
    }])
    ->get();

echo "Matches encontrados el $targetDate: " . $matches->count() . "\n\n";

foreach ($matches as $match) {
    echo "═════════════════════════════════════════════════════════════════\n";
    echo "Match #{$match->id}: {$match->home_team} vs {$match->away_team}\n";
    echo "Hora: " . $match->date->format('Y-m-d H:i') . "\n";
    echo "Score: {$match->home_team_score}-{$match->away_team_score}\n";
    echo "Status: {$match->status}\n";
    echo "═════════════════════════════════════════════════════════════════\n\n";

    $questions = $match->questions;
    echo "Preguntas: " . $questions->count() . "\n\n";

    foreach ($questions as $q) {
        $verified = $q->result_verified_at ? "✅ " . $q->result_verified_at : "❌ NO";
        $pointsAssigned = 0;

        // Contar puntos asignados a usuarios por esta pregunta
        $pointsLog = \DB::table('points_history')
            ->where('question_id', $q->id)
            ->sum('points');

        echo "  Pregunta #$q->id\n";
        echo "    • Texto: " . substr($q->text, 0, 80) . "...\n";
        echo "    • Tipo: $q->type\n";
        echo "    • Puntos posibles: $q->points\n";
        echo "    • Verificada: $verified\n";
        echo "    • Puntos asignados: $pointsLog\n";

        if ($q->result_verified_at) {
            $answers = $q->answers;
            echo "    • Respuestas registradas: " . $answers->count() . "\n";

            // Ver detalles de respuestas
            foreach ($answers->take(3) as $answer) {
                $user = User::find($answer->user_id);
                $isCorrect = $q->options()
                    ->where('id', $answer->option_id)
                    ->where('is_correct', 1)
                    ->exists();

                $status = $isCorrect ? "✅ CORRECTA" : "❌ INCORRECTA";
                echo "      - Usuario #$answer->user_id: $status\n";
            }

            if ($answers->count() > 3) {
                echo "      ... y " . ($answers->count() - 3) . " más\n";
            }
        }

        echo "\n";
    }

    echo "\n";
}

// Estadísticas generales
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  ESTADÍSTICAS GENERALES DEL 31-ENE-2026                       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$totalMatches = FootballMatch::whereDate('date', $targetDate)->count();
$finishedMatches = FootballMatch::whereDate('date', $targetDate)
    ->whereIn('status', ['Finished', 'Match Finished', 'FINISHED'])
    ->count();

$totalQuestions = Question::whereHas('football_match', function($q) {
    $q->whereDate('date', '2026-01-31');
})->count();

$verifiedQuestions = Question::whereHas('football_match', function($q) {
    $q->whereDate('date', '2026-01-31');
})->whereNotNull('result_verified_at')->count();

$unverifiedQuestions = $totalQuestions - $verifiedQuestions;

echo "Total matches: $totalMatches\n";
echo "Matches finalizados: $finishedMatches\n";
echo "Total preguntas: $totalQuestions\n";
echo "Preguntas verificadas: $verifiedQuestions\n";
echo "Preguntas sin verificar: $unverifiedQuestions\n\n";

// Puntos totales asignados
$totalPointsAssigned = \DB::table('points_history')
    ->join('questions', 'points_history.question_id', '=', 'questions.id')
    ->join('football_matches', 'questions.football_match_id', '=', 'football_matches.id')
    ->whereDate('football_matches.date', $targetDate)
    ->sum('points_history.points');

echo "Total puntos asignados: $totalPointsAssigned\n";

// Top usuarios del día
echo "\n📊 TOP USUARIOS POR PUNTOS (31-ENE-2026):\n";
$topUsers = \DB::table('points_history')
    ->join('questions', 'points_history.question_id', '=', 'questions.id')
    ->join('football_matches', 'questions.football_match_id', '=', 'football_matches.id')
    ->join('users', 'points_history.user_id', '=', 'users.id')
    ->whereDate('football_matches.date', $targetDate)
    ->select('users.id', 'users.name', \DB::raw('SUM(points_history.points) as total_points'))
    ->groupBy('users.id', 'users.name')
    ->orderByDesc('total_points')
    ->limit(5)
    ->get();

if ($topUsers->count() > 0) {
    foreach ($topUsers as $user) {
        echo "  $user->name: {$user->total_points} puntos\n";
    }
} else {
    echo "  ⚠️  No hay puntos asignados\n";
}

echo "\n═════════════════════════════════════════════════════════════════\n";
