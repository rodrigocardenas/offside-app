<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use App\Models\Question;
use App\Services\QuestionEvaluationService;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;

class RepairQuestionVerification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questions:repair
                            {--match-id= : Reparar solo un partido}
                            {--status=Match\ Finished : Estado del partido a buscar}
                            {--min-hours=1 : Partidos finalizados hace al menos N horas}
                            {--max-hours=72 : Partidos finalizados hace como máximo N horas}
                            {--only-unverified : Solo preguntas sin verificar}
                            {--reprocess-all : Reprocesar todas las preguntas del partido}
                            {--limit=0 : Limitar preguntas procesadas (0 = sin límite)}
                            {--no-grounding : Deshabilitar búsqueda web de Gemini (más rápido)}
                            {--show-details : Mostrar detalles de cada pregunta}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Reparar verificación de preguntas con múltiples opciones. Útil para debuggear y reprocesar.';

    protected QuestionEvaluationService $evaluationService;

    /**
     * Create a new command instance.
     */
    public function __construct(QuestionEvaluationService $evaluationService)
    {
        parent::__construct();
        $this->evaluationService = $evaluationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->info('║ Reparación de Verificación de Preguntas (Modo Diagnóstico)    ║');
        $this->info('╚═══════════════════════════════════════════════════════════════╝');

        // ✅ IMPORTANTE: No permitir bloqueos en Gemini cuando ejecutamos comando interactivo
        // Esto evita que el comando se quede esperando 90+ segundos en rate limit
        GeminiService::setAllowBlocking(false);
        $this->line("   ⚠️  Modo non-blocking: Si Gemini está rate-limitado, se saltarán las preguntas");

        // ✅ Opción para deshabilitar grounding si hay timeouts
        if ($this->option('no-grounding')) {
            GeminiService::setDisableGrounding(true);
            $this->line("   ⚡ Grounding deshabilitado: las búsquedas web serán más rápidas");
        }

        $matchId = $this->option('match-id');
        $status = $this->option('status');
        $minHours = (int) $this->option('min-hours');
        $maxHours = (int) $this->option('max-hours');
        $onlyUnverified = $this->option('only-unverified');
        $reprocessAll = $this->option('reprocess-all');
        $limit = (int) $this->option('limit');
        $showDetails = $this->option('show-details');

        try {
            // ==================== PASO 1: Buscar partidos ====================
            $this->info("\n📋 PASO 1: Buscando partidos...");

            $matchQuery = FootballMatch::query();

            if ($matchId) {
                $matchQuery->where('id', $matchId);
                $this->line("   Filtro: Match ID = {$matchId}");
            } else {
                $matchQuery->where('status', $status);
                $this->line("   Filtro: Status = {$status}");

                $now = now();
                $minTime = $now->copy()->subHours($minHours);
                $maxTime = $now->copy()->subHours($maxHours);

                $matchQuery->whereBetween('updated_at', [$maxTime, $minTime]);
                $this->line("   Filtro: Finalizados entre {$minHours} y {$maxHours} horas atrás");
            }

            $matches = $matchQuery->with('questions.options', 'questions.answers')->get();

            if ($matches->isEmpty()) {
                $this->warn("❌ No hay partidos encontrados");
                return 0;
            }

            $this->info("✅ Encontrados {$matches->count()} partidos");

            // ==================== PASO 2: Procesar cada partido ====================
            $this->info("\n📊 PASO 2: Procesando partidos...\n");

            $totalQuestions = 0;
            $verifiedQuestions = 0;
            $unverifiedQuestions = 0;
            $errorQuestions = 0;
            $totalPointsAssigned = 0;

            foreach ($matches as $match) {
                $this->info("\n🏟️  {$match->home_team} vs {$match->away_team} ({$match->score})");
                $this->line("   Match ID: {$match->id} | Status: {$match->status}");

                // Mostrar información del partido
                $statistics = is_string($match->statistics)
                    ? json_decode($match->statistics, true)
                    : $match->statistics;

                if (is_array($statistics)) {
                    $this->line("   Datos: " . ($statistics['source'] ?? 'Unknown'));
                    $hasEvents = !empty($statistics['has_detailed_events']);
                    $this->line("   Eventos detallados: " . ($hasEvents ? "✅ SÍ" : "❌ NO"));
                }

                // Procesar preguntas
                $questions = $match->questions;

                if ($questions->isEmpty()) {
                    $this->line("   ⏭️  Sin preguntas asociadas");
                    continue;
                }

                // Filtrar preguntas según opciones
                if ($onlyUnverified) {
                    $questions = $questions->whereNull('result_verified_at');
                }

                if ($reprocessAll) {
                    // Resetear verified_at para reprocesar todas
                    foreach ($questions as $q) {
                        $q->result_verified_at = null;
                        $q->save();
                    }
                }

                $this->line("   📌 {$questions->count()} preguntas a procesar");
                if ($limit > 0) {
                    $this->line("   ⏱️  Límite: primeras {$limit} preguntas");
                }

                // ✅ Procesar preguntas con límite de tiempo
                // Si Gemini está disponible, procesar. Si no, saltar.
                $processedOk = 0;
                $processedSkipped = 0;
                $processedCount = 0;

                foreach ($questions as $question) {
                    // Aplicar límite si está configurado
                    if ($limit > 0 && $processedCount >= $limit) {
                        $this->line("   ⏭️  Límite de {$limit} preguntas alcanzado - saltando resto");
                        break;
                    }
                    $processedCount++;

                    // Timeout de 15 segundos por pregunta
                    try {
                        $this->processQuestion($question, $match, $showDetails,
                            $verifiedQuestions, $unverifiedQuestions, $errorQuestions, $totalQuestions, $totalPointsAssigned);
                        $processedOk++;
                    } catch (\Exception $e) {
                        $errorMsg = $e->getMessage();
                        // Si es rate limit o timeout, saltar el resto de preguntas
                        if (strpos($errorMsg, 'Rate limited') !== false ||
                            strpos($errorMsg, 'timeout') !== false ||
                            strpos($errorMsg, 'Timeout') !== false) {
                            $this->line("   ⚠️  Gemini no disponible - saltando preguntas restantes");
                            $processedSkipped++;
                            break;
                        }
                        $processedSkipped++;
                    }
                }

                if ($showDetails && ($processedOk > 0 || $processedSkipped > 0)) {
                    $this->line("   ✅ Procesadas: {$processedOk} | ⚠️  Saltadas: {$processedSkipped}");
                }
            }

            // ==================== PASO 3: Resumen ====================
            $this->info("\n" . str_repeat("═", 70));
            $this->info("✅ REPARACIÓN COMPLETADA");
            $this->info(str_repeat("═", 70));

            $this->line("\n📊 ESTADÍSTICAS DE VERIFICACIÓN:");
            $this->line("  ├─ Total procesadas: {$totalQuestions}");
            $this->line("  ├─ Verificadas: {$verifiedQuestions} ✅");
            $this->line("  ├─ Sin opciones correctas: {$unverifiedQuestions} ⏭️");
            $this->line("  └─ Errores: {$errorQuestions} ❌");

            if ($totalQuestions > 0) {
                $percentage = round(($verifiedQuestions / $totalQuestions) * 100, 1);
                $this->line("\n💯 Tasa de éxito: {$percentage}%");
            }

            // ✅ DEDUPLICATION STATS
            $dedupStats = $this->evaluationService->getDeduplicationStats();
            if ($dedupStats['template_cache_size'] > 0) {
                $this->line("\n🚀 ESTADÍSTICAS DE DEDUPLICACIÓN:");
                $this->line("  ├─ Templates únicos verificados: {$dedupStats['template_cache_size']}");
                $this->line("  ├─ Estimado de preguntas ahorradas: ~" . ($totalQuestions - $dedupStats['template_cache_size']));

                if ($totalQuestions > 0) {
                    $savedPercentage = round((($totalQuestions - $dedupStats['template_cache_size']) / $totalQuestions) * 100, 1);
                    $this->line("  └─ Reducción de API calls: {$savedPercentage}%");
                }
            }

            $this->line("\n💰 Puntos totales asignados: {$totalPointsAssigned}");

            Log::info("Reparación de verificación completada", [
                'matches_processed' => $matches->count(),
                'questions_total' => $totalQuestions,
                'questions_verified' => $verifiedQuestions,
                'questions_unverified' => $unverifiedQuestions,
                'errors' => $errorQuestions,
                'points_assigned' => $totalPointsAssigned,
                'dedup_templates_cached' => $dedupStats['template_cache_size'],
                'dedup_estimated_savings' => $totalQuestions - $dedupStats['template_cache_size'],
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error crítico: " . $e->getMessage());
            Log::error("Error en RepairQuestionVerification command", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Procesar una pregunta individual
     */
    private function processQuestion($question, $match, $showDetails, &$verifiedQuestions, &$unverifiedQuestions, &$errorQuestions, &$totalQuestions, &$totalPointsAssigned): void
    {
        try {
            $totalQuestions++;

            // Evaluar pregunta
            $correctOptionIds = $this->evaluationService->evaluateQuestion($question, $match);

            if (empty($correctOptionIds)) {
                $unverifiedQuestions++;

                if ($showDetails) {
                    $this->line("      ⏭️  {$question->title} (Sin opción correcta)");
                }
                return;
            }

            // Actualizar opciones
            foreach ($question->options as $option) {
                $wasCorrect = $option->is_correct;
                $option->is_correct = in_array($option->id, $correctOptionIds);

                if ($wasCorrect !== $option->is_correct) {
                    $option->save();
                }
            }

            // Actualizar respuestas y puntos
            foreach ($question->answers as $answer) {
                $wasCorrect = $answer->is_correct;
                $wasPointsEarned = $answer->points_earned ?? 0;

                $answer->is_correct = in_array($answer->question_option_id, $correctOptionIds);
                $answer->points_earned = $answer->is_correct ? ($question->points ?? 300) : 0;

                // Guardar si cambió el estado O si cambiaron los puntos
                if ($wasCorrect !== $answer->is_correct || $wasPointsEarned !== $answer->points_earned) {
                    $answer->save();
                    $totalPointsAssigned += $answer->points_earned;
                }
            }

            // Marcar como verificada
            $question->result_verified_at = now();
            $question->save();

            $verifiedQuestions++;

            if ($showDetails) {
                $optionCount = count($correctOptionIds);
                $answerCount = $question->answers->count();
                $this->line("      ✅ {$question->title} ({$optionCount} opciones correctas, {$answerCount} respuestas)");
            }

        } catch (\Exception $e) {
            $errorQuestions++;

            if ($showDetails) {
                $this->line("      ❌ {$question->title} - Error: " . substr($e->getMessage(), 0, 60));
            }

            Log::error("Error verificando pregunta {$question->id}", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
