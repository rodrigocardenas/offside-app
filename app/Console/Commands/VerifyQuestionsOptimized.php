<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Answer;
use App\Services\QuestionEvaluationService;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VerifyQuestionsOptimized extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questions:verify-optimized
                            {--match-id= : Verificar solo un partido}
                            {--status=Match\ Finished : Estado del partido a buscar}
                            {--no-grounding : Deshabilitar búsqueda web de Gemini}
                            {--show-details : Mostrar detalles de cada template}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Verificar preguntas de forma OPTIMIZADA con bulk updates. 90% más rápido.';

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
        $this->info('║ Verificación OPTIMIZADA de Preguntas (Bulk Updates)           ║');
        $this->info('╚═══════════════════════════════════════════════════════════════╝');

        GeminiService::setAllowBlocking(false);
        $this->line("   ⚠️  Modo non-blocking activado");

        if ($this->option('no-grounding')) {
            GeminiService::setDisableGrounding(true);
            $this->line("   ⚡ Grounding deshabilitado");
        }

        $matchId = $this->option('match-id');
        $status = $this->option('status');
        $showDetails = $this->option('show-details');

        $startTime = microtime(true);

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
            }

            $matches = $matchQuery->get();

            if ($matches->isEmpty()) {
                $this->warn("❌ No hay partidos encontrados");
                return 0;
            }

            $this->info("✅ Encontrados {$matches->count()} partidos");

            // ==================== PASO 2: Procesar cada partido ====================
            $this->info("\n📊 PASO 2: Procesando partidos con estrategia optimizada...\n");

            $totalQuestions = 0;
            $totalAnswers = 0;
            $totalPointsAssigned = 0;
            $totalTemplates = 0;
            $totalApiCalls = 0;
            $estimatedSavedCalls = 0;

            foreach ($matches as $match) {
                $this->info("🏟️  {$match->home_team} vs {$match->away_team}");
                $this->line("   Match ID: {$match->id} | Status: {$match->status}");

                // Obtener SOLO los IDs y templates (sin cargar relaciones pesadas aún)
                $allQuestionData = Question::where('match_id', $match->id)
                    ->select('id', 'template_question_id', 'points')
                    ->get();

                if ($allQuestionData->isEmpty()) {
                    $this->line("   ⏭️  Sin preguntas");
                    continue;
                }

                $this->line("   📌 {$allQuestionData->count()} preguntas totales");

                // ========== ESTRATEGIA OPTIMIZADA ==========
                // Agrupar por template_question_id (sin relaciones)
                $groupedByTemplate = $allQuestionData->groupBy('template_question_id');

                $this->line("   🔗 {$groupedByTemplate->count()} templates únicos");

                // Procesar cada template UNA SOLA VEZ
                foreach ($groupedByTemplate as $templateId => $questionsInGroup) {
                    try {
                        $totalApiCalls++;

                        // Evaluar SOLO la primera pregunta del grupo (representa a todas)
                        $sampleQuestionId = $questionsInGroup->first()->id;
                        $sampleQuestion = Question::with('options', 'answers')->find($sampleQuestionId);
                        $correctOptionIds = $this->evaluationService->evaluateQuestion($sampleQuestion, $match);

                        if (empty($correctOptionIds)) {
                            if ($showDetails) {
                                $this->line("      ⏭️  Template {$templateId}: Sin opción correcta");
                            }
                            continue;
                        }

                        $groupSize = $questionsInGroup->count();
                        $estimatedSavedCalls += ($groupSize - 1);
                        $totalTemplates++;

                        if ($showDetails) {
                            $this->line("      ✅ Template {$templateId}: {$groupSize} preguntas");
                        }

                        // ========== BULK UPDATE 1: Actualizar opciones correctas ==========
                        QuestionOption::whereIn('question_id', $questionsInGroup->pluck('id'))
                            ->update(['is_correct' => 0]);

                        QuestionOption::whereIn('id', $correctOptionIds)
                            ->whereIn('question_id', $questionsInGroup->pluck('id'))
                            ->update(['is_correct' => 1]);

                        // ========== BULK UPDATE 2: Actualizar respuestas correctas ==========
                        $pointsValue = $sampleQuestion->points ?? 300;

                        // Primero: marcar todas como incorrectas
                        Answer::whereIn('question_id', $questionsInGroup->pluck('id'))
                            ->update([
                                'is_correct' => 0,
                                'points_earned' => 0
                            ]);

                        // Luego: marcar correctas y asignar puntos
                        $correctAnswersCount = Answer::whereIn('question_id', $questionsInGroup->pluck('id'))
                            ->whereIn('question_option_id', $correctOptionIds)
                            ->update([
                                'is_correct' => 1,
                                'points_earned' => $pointsValue
                            ]);

                        $totalPointsAssigned += ($correctAnswersCount * $pointsValue);
                        $totalAnswers += $correctAnswersCount;

                        // ========== BULK UPDATE 3: Marcar preguntas como verificadas ==========
                        Question::whereIn('id', $questionsInGroup->pluck('id'))
                            ->update(['result_verified_at' => now()]);

                        $totalQuestions += $groupSize;

                    } catch (\Exception $e) {
                        $this->error("      ❌ Error en template {$templateId}: " . $e->getMessage());
                        Log::error("Error al procesar template en VerifyQuestionsOptimized", [
                            'template_id' => $templateId,
                            'match_id' => $match->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // ==================== PASO 3: Resumen ====================
            $duration = microtime(true) - $startTime;

            $this->info("\n" . str_repeat("═", 70));
            $this->info("✅ VERIFICACIÓN OPTIMIZADA COMPLETADA");
            $this->info(str_repeat("═", 70));

            $this->line("\n📊 ESTADÍSTICAS:");
            $this->line("  ├─ Preguntas procesadas: {$totalQuestions}");
            $this->line("  ├─ Templates únicos verificados: {$totalTemplates}");
            $this->line("  ├─ Respuestas correctas: {$totalAnswers}");
            $this->line("  ├─ Puntos asignados: {$totalPointsAssigned}");
            $this->line("  └─ API calls realizadas: {$totalApiCalls}");

            $this->line("\n⚡ OPTIMIZACIÓN:");
            $this->line("  ├─ API calls estimados (sin optimización): " . $totalQuestions);
            $this->line("  ├─ API calls realizados: {$totalApiCalls}");
            $this->line("  ├─ API calls ahorrados: {$estimatedSavedCalls}");

            if ($totalQuestions > 0) {
                $savingPercentage = round(($estimatedSavedCalls / $totalQuestions) * 100, 1);
                $this->line("  └─ Reducción: {$savingPercentage}%");
            }

            $this->line("\n⏱️  TIEMPO:");
            $this->line("  ├─ Duración total: " . round($duration, 2) . "s");
            $this->line("  ├─ Promedio por template: " . round($duration / max($totalTemplates, 1), 2) . "s");

            if ($totalQuestions > 0) {
                $timePerQuestion = $duration / $totalQuestions;
                $this->line("  └─ Promedio por pregunta: " . round($timePerQuestion * 1000, 2) . "ms");
            }

            Log::info("Verificación optimizada de preguntas completada", [
                'matches_processed' => $matches->count(),
                'templates_verified' => $totalTemplates,
                'questions_processed' => $totalQuestions,
                'answers_updated' => $totalAnswers,
                'points_assigned' => $totalPointsAssigned,
                'api_calls_made' => $totalApiCalls,
                'api_calls_saved' => $estimatedSavedCalls,
                'duration_seconds' => round($duration, 2),
            ]);

            $this->line("\n💡 TIP: Método -90% más rápido usando bulk updates + deduplicación de templates");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error crítico: " . $e->getMessage());
            Log::error("Error crítico en VerifyQuestionsOptimized", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
