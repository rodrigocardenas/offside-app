<?php
/**
 * Script para simular y probar el flujo mejorado de verificación de preguntas
 *
 * Flujo:
 * 1. ProcessMatchBatchJob - Obtiene scores básicos
 * 2. ExtractMatchDetailsJob - Intenta obtener eventos
 * 3. VerifyQuestionResultsJob - Verifica preguntas
 */

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Jobs\ProcessMatchBatchJob;
use App\Jobs\ExtractMatchDetailsJob;
use App\Jobs\VerifyQuestionResultsJob;
use App\Models\FootballMatch;
use App\Models\Question;

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║ TEST FLUJO MEJORADO: Obtención → Extracción → Verificación   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

// Buscar un partido finalizadocon preguntas
$match = FootballMatch::where('status', 'Match Finished')
    ->whereHas('questions')
    ->orderByDesc('id')
    ->first();

if (!$match) {
    echo "\n❌ No hay partidos finalizados con preguntas\n";
    exit;
}

echo "\n🏟️  PARTIDO SELECCIONADO:\n";
echo "   Match ID: {$match->id}\n";
echo "   {$match->home_team} vs {$match->away_team}\n";
echo "   Score actual: {$match->score}\n";
echo "   Events field: " . (is_string($match->events) ?
    (strlen($match->events) . " caracteres") :
    gettype($match->events)) . "\n";

$questions = $match->questions()->limit(3)->get();
echo "   Preguntas: " . $questions->count() . "\n";

// ============ FASE 1: Mostrar estado actual ============
echo "\n" . str_repeat("─", 70) . "\n";
echo "FASE 0: ESTADO ACTUAL\n";
echo "─" . str_repeat("─", 69) . "\n";

foreach ($questions as $question) {
    echo "  ❓ {$question->title}\n";
    echo "     ├─ Type: {$question->type}\n";
    echo "     ├─ Verified at: " . ($question->result_verified_at ? "✅ " . $question->result_verified_at : "❌ NULL") . "\n";

    $correctCount = $question->options()->where('is_correct', true)->count();
    echo "     └─ Opciones correctas: {$correctCount}\n";
}

// ============ FASE 1: ProcessMatchBatchJob ============
echo "\n" . str_repeat("─", 70) . "\n";
echo "FASE 1: ProcessMatchBatchJob (Obtener scores básicos)\n";
echo "─" . str_repeat("─", 69) . "\n";

echo "⏳ Ejecutando ProcessMatchBatchJob manualmente...\n";

try {
    $job = new ProcessMatchBatchJob([$match->id], 1);
    $footballService = app(\App\Services\FootballService::class);
    $geminiService = app(\App\Services\GeminiService::class);

    $job->handle($footballService, $geminiService);

    echo "✅ ProcessMatchBatchJob completado\n";

    $match->refresh();
    echo "\n   Resultados:\n";
    echo "   ├─ Score: {$match->score}\n";
    echo "   ├─ home_team_score: {$match->home_team_score}\n";
    echo "   ├─ away_team_score: {$match->away_team_score}\n";
    echo "   └─ Events field: " . (strlen($match->events ?? '') . " caracteres\n");
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// ============ FASE 2: ExtractMatchDetailsJob ============
echo "\n" . str_repeat("─", 70) . "\n";
echo "FASE 2: ExtractMatchDetailsJob (Extraer eventos)\n";
echo "─" . str_repeat("─", 69) . "\n";

echo "⏳ Ejecutando ExtractMatchDetailsJob manualmente...\n";

try {
    $job = new ExtractMatchDetailsJob();
    $geminiService = app(\App\Services\GeminiService::class);

    $job->handle($geminiService);

    echo "✅ ExtractMatchDetailsJob completado\n";

    $match->refresh();

    // Verificar si tiene eventos JSON
    $events = is_string($match->events) ? json_decode($match->events, true) : [];

    if (is_array($events) && count($events) > 0) {
        echo "\n   ✅ EVENTOS EXTRAÍDOS: " . count($events) . " eventos\n";
        foreach (array_slice($events, 0, 3) as $event) {
            $min = $event['minute'] ?? '?';
            $type = $event['type'] ?? '?';
            $team = $event['team'] ?? '?';
            echo "      ├─ Min {$min}: {$type} ({$team})\n";
        }
    } else {
        echo "\n   ⚠️  Sin eventos JSON (esto es normal si Gemini no devolvió eventos)\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// ============ FASE 3: VerifyQuestionResultsJob ============
echo "\n" . str_repeat("─", 70) . "\n";
echo "FASE 3: VerifyQuestionResultsJob (Verificar preguntas)\n";
echo "─" . str_repeat("─", 69) . "\n";

echo "⏳ Ejecutando VerifyQuestionResultsJob manualmente...\n";

try {
    $job = new VerifyQuestionResultsJob();
    $evaluationService = app(\App\Services\QuestionEvaluationService::class);

    $job->handle($evaluationService);

    echo "✅ VerifyQuestionResultsJob completado\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// ============ RESULTADO FINAL ============
echo "\n" . str_repeat("─", 70) . "\n";
echo "RESULTADO FINAL\n";
echo "─" . str_repeat("─", 69) . "\n";

$match->refresh();
$questions = $match->questions()->limit(3)->get();

foreach ($questions as $question) {
    $question->refresh();
    echo "  ❓ {$question->title}\n";
    echo "     ├─ Type: {$question->type}\n";
    echo "     ├─ Verified: " . ($question->result_verified_at ? "✅ YES" : "❌ NO") . "\n";

    $correctCount = $question->options()->where('is_correct', true)->count();
    $totalCount = $question->options()->count();

    if ($correctCount > 0) {
        echo "     ├─ Opciones correctas: {$correctCount}/{$totalCount} ✅\n";
        $question->options()->where('is_correct', true)->limit(2)->each(function($opt) {
            echo "     │  ├─ {$opt->text}\n";
        });
    } else {
        echo "     └─ Opciones correctas: NINGUNA ❌\n";
    }
}

echo "\n" . str_repeat("═", 70) . "\n";
echo "✅ TEST COMPLETADO\n";
echo "\n💡 CONCLUSIONES:\n";
echo "   1. Si las preguntas tienen opciones correctas marcadas → ✅ Verificación funciona\n";
echo "   2. Si result_verified_at se actualiza → ✅ Job se ejecutó\n";
echo "   3. Si no hay eventos JSON pero preguntas score-based verifican → ✅ Fallback funciona\n";
echo "   4. Si hay eventos JSON → ✅ ExtractMatchDetailsJob funcionó\n";

?>
