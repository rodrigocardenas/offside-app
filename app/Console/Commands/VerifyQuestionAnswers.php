<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use App\Models\Question;
use App\Services\QuestionEvaluationService;
use Illuminate\Support\Facades\Log;

class VerifyQuestionAnswers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questions:verify-answers
                            {--match-id= : Verificar solo un partido específico}
                            {--force : Forzar reverificación aunque ya esté verificada}
                            {--limit=50 : Máximo número de preguntas a procesar}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Verificar respuestas de preguntas y asignar puntos manualmente. Útil si los jobs no terminan.';

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
        $this->info('║ Verificación Manual de Respuestas de Preguntas               ║');
        $this->info('╚═══════════════════════════════════════════════════════════════╝');

        $matchId = $this->option('match-id');
        $force = $this->option('force');
        $limit = (int) $this->option('limit');

        try {
            // ==================== PASO 1: Buscar preguntas ====================
            $this->info("\n📋 PASO 1: Buscando preguntas a verificar...");

            $query = Question::query();

            // Si se especifica match-id, filtrar por ese partido
            if ($matchId) {
                $query->where('football_match_id', $matchId);
                $this->line("   Filtro: Match ID = {$matchId}");
            }

            // Si no es force, solo preguntas no verificadas
            if (!$force) {
                $query->whereNull('result_verified_at');
                $this->line("   Filtro: Sin verificar (result_verified_at = NULL)");
            } else {
                $this->line("   Filtro: Todas (forzar reverificación)");
            }

            // Cargar con relaciones
            $questions = $query
                ->with('football_match', 'options', 'answers')
                ->limit($limit)
                ->get();

            if ($questions->isEmpty()) {
                $this->warn("❌ No hay preguntas para verificar");
                return 0;
            }

            $this->info("✅ Encontradas {$questions->count()} preguntas");

            // ==================== PASO 2: Verificar cada pregunta ====================
            $this->info("\n📊 PASO 2: Verificando preguntas y asignando puntos...\n");

            $progressBar = $this->output->createProgressBar($questions->count());
            $progressBar->start();

            $successCount = 0;
            $failureCount = 0;
            $skippedCount = 0;

            foreach ($questions as $question) {
                $progressBar->advance();

                try {
                    $match = $question->football_match;

                    // Validar que el match esté finalizado
                    if (!$match || !in_array($match->status, ['FINISHED', 'Match Finished'])) {
                        $skippedCount++;
                        continue;
                    }

                    // Evaluar pregunta usando QuestionEvaluationService
                    $correctOptionIds = $this->evaluationService->evaluateQuestion($question, $match);

                    // Actualizar opciones correctas
                    foreach ($question->options as $option) {
                        $wasCorrect = $option->is_correct;
                        $option->is_correct = in_array($option->id, $correctOptionIds);

                        if ($wasCorrect !== $option->is_correct) {
                            $option->save();
                        }
                    }

                    // Actualizar respuestas de usuarios y asignar puntos
                    $answersUpdated = 0;
                    foreach ($question->answers as $answer) {
                        $wasCorrect = $answer->is_correct;
                        $answer->is_correct = in_array($answer->question_option_id, $correctOptionIds);
                        $answer->points_earned = $answer->is_correct ? ($question->points ?? 300) : 0;

                        if ($wasCorrect !== $answer->is_correct) {
                            $answer->save();
                            $answersUpdated++;
                        }
                    }

                    // Marcar pregunta como verificada
                    $question->result_verified_at = now();
                    $question->save();

                    $successCount++;

                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error("Error verificando pregunta {$question->id}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $progressBar->finish();

            // ==================== PASO 3: Resumen ====================
            $this->info("\n\n" . str_repeat("═", 70));
            $this->info("✅ VERIFICACIÓN COMPLETADA");
            $this->info(str_repeat("═", 70));

            $this->line("Resultados:");
            $this->line("  ├─ Exitosas: {$successCount} ✅");
            $this->line("  ├─ Fallidas: {$failureCount} ❌");
            $this->line("  └─ Saltadas: {$skippedCount} ⏭️");

            // Estadísticas
            if ($questions->count() > 0) {
                $percentage = round(($successCount / $questions->count()) * 100, 1);
                $this->line("\nTasa de éxito: {$percentage}%");
            }

            // Información por tipo de pregunta
            $this->info("\n📈 DETALLES POR TIPO:");
            $questionsByType = $questions
                ->where('result_verified_at', '!=', null)
                ->groupBy('type')
                ->map->count();

            foreach ($questionsByType as $type => $count) {
                $this->line("  ├─ {$type}: {$count} verificadas");
            }

            // Puntos asignados
            $totalPoints = 0;
            foreach ($questions as $question) {
                foreach ($question->answers as $answer) {
                    if ($answer->is_correct) {
                        $totalPoints += $answer->points_earned ?? 0;
                    }
                }
            }

            $this->info("\n💰 PUNTOS ASIGNADOS: {$totalPoints} puntos");

            Log::info("Verificación manual de respuestas completada", [
                'total_processed' => $questions->count(),
                'success' => $successCount,
                'failures' => $failureCount,
                'skipped' => $skippedCount,
                'total_points_assigned' => $totalPoints
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error crítico: " . $e->getMessage());
            Log::error("Error en VerifyQuestionAnswers command", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
