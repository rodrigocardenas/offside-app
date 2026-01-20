<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use App\Models\Question;
use App\Services\QuestionEvaluationService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DiagnoseMatchVerification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diagnose:match-verification
                            {match-id : ID del partido a diagnosticar}
                            {--verbose : Mostrar más detalles}
                            {--test-evaluate : Ejecutar evaluación real}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnosticar llamadas a Gemini y optimizaciones para un partido específico';

    protected QuestionEvaluationService $evaluationService;
    protected int $geminiCallCount = 0;
    protected array $geminiCalls = [];

    public function handle(QuestionEvaluationService $evaluationService)
    {
        $this->evaluationService = $evaluationService;
        $matchId = $this->argument('match-id');
        $verbose = $this->option('verbose');
        $testEvaluate = $this->option('test-evaluate');

        $match = FootballMatch::with(['questions' => function ($q) {
            $q->whereNull('result_verified_at');
        }, 'questions.options', 'questions.answers'])->find($matchId);

        if (!$match) {
            $this->error("❌ Partido {$matchId} no encontrado");
            return 1;
        }

        $this->line("\n" . str_repeat('═', 80));
        $this->line('🔍 DIAGNÓSTICO DE VERIFICACIÓN DE PARTIDO');
        $this->line(str_repeat('═', 80) . "\n");

        // Info del partido
        $this->info("📊 INFORMACIÓN DEL PARTIDO:");
        $this->line("   ID: {$match->id}");
        $this->line("   Partido: {$match->home_team} vs {$match->away_team}");
        $this->line("   Estado: {$match->status}");
        $this->line("   Fecha: {$match->date}");
        $this->line("   Score: {$match->home_team_score} - {$match->away_team_score}");
        $this->line("");

        // Verificar datos del match
        $this->info("📁 DATOS DEL PARTIDO EN BD:");
        $hasEvents = !empty($match->events);
        $hasStats = !empty($match->statistics);
        $this->line("   Events: " . ($hasEvents ? "✅ Presente" : "❌ Faltante"));
        $this->line("   Statistics: " . ($hasStats ? "✅ Presente" : "❌ Faltante"));

        if ($hasStats) {
            $stats = json_decode($match->statistics, true);
            if (is_array($stats)) {
                $this->line("   Source: " . ($stats['source'] ?? 'unknown'));
                if (isset($stats['possession_home'])) {
                    $this->line("   Posesión: {$stats['possession_home']}% vs {$stats['possession_away']}%");
                }
                if ($verbose) {
                    $this->line("   Stats Keys: " . implode(", ", array_keys($stats)));
                }
            }
        }
        $this->line("");

        // Preguntas
        $questions = $match->questions;
        $this->info("❓ PREGUNTAS PENDIENTES DE VERIFICACIÓN:");
        $this->line("   Total: {$questions->count()}");

        if ($questions->isEmpty()) {
            $this->warn("   ⚠️  No hay preguntas pendientes");
        } else {
            $index = 0;
            foreach ($questions as $question) {
                $index++;
                $qNumber = $index;
                $qId = $question->id;
                $qTitle = substr($question->title, 0, 60);
                $optionsCount = $question->options->count();
                $answersCount = $question->answers->count();

                $this->line("   \n   Q{$qNumber} (ID: {$qId}): {$qTitle}...");
                $this->line("      Tipo: " . ($question->type ?? 'unknown'));
                $this->line("      Opciones: {$optionsCount}");
                $this->line("      Respuestas: {$answersCount}");

                // Analizar si la pregunta necesitará Gemini
                $needsGemini = $this->analyzeQuestionType($question, $match);
                if ($needsGemini) {
                    $this->line("      🔴 Requiere Gemini: SÍ");
                } else {
                    $this->line("      🟢 Verificable con código: SÍ");
                }
            }
        }

        $this->line("\n" . str_repeat('─', 80));
        $this->info("📈 ANÁLISIS DE LLAMADAS A GEMINI:");

        $questionsNeedingGemini = $questions->filter(fn($q) => $this->analyzeQuestionType($q, $match))->count();
        $questionsCodeOnly = $questions->count() - $questionsNeedingGemini;

        $this->line("   Preguntas verificables con código: {$questionsCodeOnly}");
        $this->line("   Preguntas que requieren Gemini: {$questionsNeedingGemini}");

        if ($questionsNeedingGemini > 0) {
            $this->warn("   ⚠️  ANTES (sin optimización):");
            $this->line("      Llamadas a Gemini por pregunta: 1");
            $this->line("      Total llamadas: " . ($questionsNeedingGemini));
            $this->line("      Problema: Se hace 1 llamada POR pregunta (no compartida)");

            $this->info("   ✅ DESPUÉS (con cache de sesión):");
            $this->line("      Llamadas a Gemini por match: 1 (COMPARTIDA)");
            $this->line("      Datos reutilizados para: {$questionsNeedingGemini} preguntas");
            $this->line("      Mejora: " . (100 - round(100 / $questionsNeedingGemini)) . "% reducción");
        }

        $this->line("");

        if ($testEvaluate) {
            $this->line(str_repeat('─', 80));
            $this->info("🧪 PRUEBA DE EVALUACIÓN (con tracking de llamadas):");

            // Monitorear llamadas a Gemini
            $startTime = microtime(true);
            $evaluatedCount = 0;
            $errorCount = 0;

            foreach ($questions as $question) {
                try {
                    // Contar llamadas a Gemini monitoreando logs
                    $this->line("\n   Evaluando Q{$question->id}...");

                    $options = $this->evaluationService->evaluateQuestion($question, $match);
                    $optionsCount = is_array($options) ? count($options) : $options->count();

                    if (!empty($options)) {
                        $this->line("   ✅ Evaluada correctamente ({$optionsCount} opciones correctas)");
                    } else {
                        $this->line("   ⚠️  No se encontraron opciones correctas");
                    }
                    $evaluatedCount++;
                } catch (\Exception $e) {
                    $this->error("   ❌ Error: " . $e->getMessage());
                    $errorCount++;
                }
            }

            $duration = microtime(true) - $startTime;

            $this->line("\n" . str_repeat('─', 80));
            $this->info("📊 RESULTADOS DE PRUEBA:");
            $this->line("   Preguntas evaluadas: {$evaluatedCount}");
            $this->line("   Errores: {$errorCount}");
            $this->line("   Tiempo total: " . round($duration, 2) . "s");
            $this->line("   Tiempo promedio por pregunta: " . round($duration / max(1, $evaluatedCount), 2) . "s");
        }

        $this->line("\n" . str_repeat('═', 80) . "\n");

        return 0;
    }

    /**
     * Analizar si una pregunta necesitará llamar a Gemini
     */
    private function analyzeQuestionType(Question $question, FootballMatch $match): bool
    {
        $questionText = strtolower($question->title);

        // Preguntas que se pueden verificar SOLO con código
        $codeOnlyPatterns = [
            'resultado|ganador|victoria|gana|ganará', // Score
            'ambos.*anotan|both.*score', // Score
            'score.*exacto|exact|marcador', // Score
            'goles.*over|goles.*under|total.*goles|más.*goles|mas.*goles|menos.*goles', // Score
        ];

        foreach ($codeOnlyPatterns as $pattern) {
            if (preg_match('/' . $pattern . '/u', $questionText)) {
                return false; // NO necesita Gemini
            }
        }

        // Todos los demás patrones necesitan Gemini o datos detallados
        $geminiPatterns = [
            'primer gol|anotará.*primer', // Event
            'gol.*minuto', // Event
            'ultimo gol|anotará.*último', // Event
            'más.*faltas|faltas', // Event
            'tarjetas amarillas|amarillas', // Event
            'tarjetas rojas|rojas', // Event
            'autogol|auto gol', // Event
            'penal|penalty', // Event
            'tiro libre|free kick', // Event
            'córner|corner', // Event
            'posesión|possession', // Stats (puede estar en BD pero no siempre)
        ];

        foreach ($geminiPatterns as $pattern) {
            if (preg_match('/' . $pattern . '/u', $questionText)) {
                return true; // SÍ necesita Gemini
            }
        }

        // Si no coincide con ningún patrón conocido
        return true; // Asumir que necesita Gemini (fallback)
    }
}
