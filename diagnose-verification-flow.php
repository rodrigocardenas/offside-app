<?php
/**
 * Script para diagnosticar por qué no se verifican correctamente las preguntas
 *
 * Uso: php diagnose-verification-flow.php
 */

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FootballMatch;
use App\Models\Question;
use App\Services\QuestionEvaluationService;

// ==================== PASO 1: Buscar partidos finalizados con preguntas ====================
echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║ DIAGNÓSTICO: Flujo de Verificación de Preguntas            ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";

echo "\n📊 PASO 1: Buscando partidos finalizados recientes...\n";

$matches = FootballMatch::where('status', 'Match Finished')
    ->whereDate('created_at', '>=', now()->subDays(3))
    ->orderByDesc('id')
    ->limit(10)
    ->get();

if ($matches->isEmpty()) {
    echo "❌ No hay partidos finalizados. Saliendo.\n";
    exit;
}

echo "✅ Encontrados " . $matches->count() . " partidos finalizados\n";

// ==================== PASO 2: Analizar cada partido ====================
foreach ($matches as $match) {
    echo "\n" . str_repeat("─", 70) . "\n";
    echo "🏟️  PARTIDO: {$match->home_team} vs {$match->away_team}\n";
    echo "   Match ID: {$match->id}\n";
    echo "   Score: {$match->score}\n";
    echo "   Status: {$match->status}\n";

    // ==================== VALIDAR DATOS DEL PARTIDO ====================
    echo "\n   📋 DATOS DEL PARTIDO:\n";
    echo "   ├─ home_team_score: " . ($match->home_team_score ?? 'NULL') . "\n";
    echo "   ├─ away_team_score: " . ($match->away_team_score ?? 'NULL') . "\n";

    // Validar events
    echo "   ├─ events field type: " . gettype($match->events) . "\n";
    if ($match->events) {
        if (is_string($match->events)) {
            $eventsLength = strlen($match->events);
            echo "   │  ├─ String length: {$eventsLength} caracteres\n";

            // Intentar parsear
            $parsed = json_decode($match->events, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (is_array($parsed)) {
                    echo "   │  ├─ ✅ JSON válido: " . count($parsed) . " elementos\n";

                    // Mostrar estructura
                    if (count($parsed) > 0) {
                        $first = $parsed[0];
                        if (is_array($first)) {
                            echo "   │  ├─ Estructura del primer evento:\n";
                            foreach ($first as $key => $value) {
                                echo "   │  │  ├─ {$key}: {$value}\n";
                            }
                        }
                    }
                } else {
                    echo "   │  └─ ⚠️  JSON parseado pero no es array: " . gettype($parsed) . "\n";
                }
            } else {
                echo "   │  └─ ❌ JSON inválido: " . json_last_error_msg() . "\n";
                echo "   │     Primeros 200 caracteres: " . substr($match->events, 0, 200) . "...\n";
            }
        } else {
            echo "   │  └─ No es string: " . print_r($match->events, true) . "\n";
        }
    } else {
        echo "   └─ ⚠️  events vacío o NULL\n";
    }

    // Validar statistics
    echo "   ├─ statistics: " . (is_string($match->statistics) ? 'JSON String' : gettype($match->statistics)) . "\n";
    if ($match->statistics) {
        $stats = is_string($match->statistics) ? json_decode($match->statistics, true) : $match->statistics;
        if (is_array($stats)) {
            echo "   │  ├─ source: " . ($stats['source'] ?? 'N/A') . "\n";
            echo "   │  ├─ verified: " . ($stats['verified'] ?? 'N/A') . "\n";
            echo "   │  ├─ has_detailed_events: " . ($stats['has_detailed_events'] ?? 'false') . "\n";
            echo "   │  ├─ detailed_event_count: " . ($stats['detailed_event_count'] ?? '0') . "\n";
            echo "   │  └─ timestamp: " . ($stats['timestamp'] ?? 'N/A') . "\n";
        }
    }

    // ==================== PREGUNTAS ASOCIADAS ====================
    echo "\n   ❓ PREGUNTAS ASOCIADAS:\n";
    $questions = $match->questions()->limit(5)->get();

    if ($questions->isEmpty()) {
        echo "   └─ ⚠️  Sin preguntas asociadas\n";
    } else {
        echo "   ├─ Total: " . $questions->count() . " preguntas\n";

        $evaluationService = app(QuestionEvaluationService::class);

        foreach ($questions as $idx => $question) {
            $isLast = ($idx === $questions->count() - 1);
            $prefix = $isLast ? "└─" : "├─";
            $childPrefix = $isLast ? "   " : "│  ";

            echo "   {$prefix} [{$question->id}] {$question->title}\n";
            echo "   {$childPrefix}├─ Type: " . ($question->type ?? 'N/A') . "\n";
            echo "   {$childPrefix}├─ result_verified_at: " . ($question->result_verified_at ? '✅ ' . $question->result_verified_at : '❌ NULL') . "\n";

            // Opciones y su estado
            $options = $question->options;
            echo "   {$childPrefix}├─ Opciones (" . $options->count() . "):\n";
            foreach ($options as $opt_idx => $option) {
                $opt_prefix = ($opt_idx === $options->count() - 1) ? "└─" : "├─";
                echo "   {$childPrefix}│  {$opt_prefix} [{$option->id}] {$option->text} (is_correct: " . ($option->is_correct ? '✅' : '❌') . ")\n";
            }

            // Intentar evaluar manualmente
            echo "   {$childPrefix}└─ Evaluación manual:\n";
            try {
                $correctIds = $evaluationService->evaluateQuestion($question, $match);
                if (empty($correctIds)) {
                    echo "   {$childPrefix}   ⚠️  Retornó array vacío\n";
                } else {
                    echo "   {$childPrefix}   ✅ Opciones correctas: [" . implode(", ", $correctIds) . "]\n";
                }
            } catch (\Exception $e) {
                echo "   {$childPrefix}   ❌ Error: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "\n" . str_repeat("═", 70) . "\n";
echo "✅ Diagnóstico completado\n";
echo "\n💡 PRÓXIMOS PASOS:\n";
echo "1. Revisar si events está en JSON válido\n";
echo "2. Revisar si QuestionEvaluationService::evaluateQuestion() retorna opciones\n";
echo "3. Revisar si result_verified_at se actualiza\n";
echo "4. Buscar errores en storage/logs/laravel.log\n";

?>
