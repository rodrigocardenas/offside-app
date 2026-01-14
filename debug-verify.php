<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FootballMatch;
use App\Models\Question;
use App\Services\QuestionEvaluationService;

// Buscar preguntas sin verificar
$unverifiedQuestions = Question::whereNull('result_verified_at')
    ->with('football_match', 'options', 'answers')
    ->limit(10)
    ->get();

echo "\n=== PREGUNTAS SIN VERIFICAR ===\n";
echo "Total: " . $unverifiedQuestions->count() . "\n\n";

foreach ($unverifiedQuestions as $question) {
    echo "=" . str_repeat("=", 70) . "\n";
    echo "Q{$question->id}: {$question->title}\n";
    echo "Type: {$question->type}\n";
    echo "Match ID: {$question->match_id}\n";
    echo "Result Verified At: {$question->result_verified_at}\n";

    if (!$question->football_match) {
        echo "❌ NO TIENE MATCH ASOCIADO\n";
        continue;
    }

    $match = $question->football_match;
    echo "\nMatch: {$match->home_team} vs {$match->away_team}\n";
    echo "Match Status: {$match->status}\n";
    echo "Score: {$match->score}\n";
    echo "Events field type: " . gettype($match->events) . "\n";

    if ($match->events) {
        $eventsDecoded = json_decode($match->events, true);
        if (is_array($eventsDecoded)) {
            echo "Events (JSON): " . json_encode($eventsDecoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo "Events (Raw): " . substr($match->events, 0, 100) . "...\n";
        }
    } else {
        echo "Events: NULL or empty\n";
    }

    echo "Statistics: " . substr($match->statistics, 0, 150) . "...\n";

    // Revisar options
    echo "\nOptions ({$question->options->count()}):\n";
    foreach ($question->options as $opt) {
        echo "  - OPT{$opt->id}: {$opt->text} (is_correct: {$opt->is_correct})\n";
    }

    // Revisar answers
    echo "\nAnswers ({$question->answers->count()}):\n";
    foreach ($question->answers as $ans) {
        echo "  - ANS{$ans->id}: Option {$ans->question_option_id}, is_correct: {$ans->is_correct}, points_earned: {$ans->points_earned}\n";
    }

    // Evaluar manualmente
    $evaluationService = $app->make(QuestionEvaluationService::class);
    echo "\n--- EVALUACIÓN MANUAL ---\n";
    try {
        $correctOptionIds = $evaluationService->evaluateQuestion($question, $match);
        echo "Resultado: " . json_encode($correctOptionIds) . "\n";
        if (empty($correctOptionIds)) {
            echo "⚠️ NO SE PUDIERON DETERMINAR OPCIONES CORRECTAS\n";
        }
    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }

    echo "\n";
}
