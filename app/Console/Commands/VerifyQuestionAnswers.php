<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use App\Models\Question;
use App\Services\GeminiService;
use App\Services\QuestionEvaluationService;
use Illuminate\Support\Collection;
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
                            {--limit=50 : Máximo número de preguntas a procesar}
                            {--hydrate-events : Intentar descargar eventos antes de verificar (usa Gemini)}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Verificar respuestas de preguntas y asignar puntos manualmente. Útil si los jobs no terminan.';

    protected QuestionEvaluationService $evaluationService;
    protected GeminiService $geminiService;

    /**
     * Create a new command instance.
     */
    public function __construct(QuestionEvaluationService $evaluationService, GeminiService $geminiService)
    {
        parent::__construct();
        $this->evaluationService = $evaluationService;
        $this->geminiService = $geminiService;
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
        $hydrateEvents = $this->option('hydrate-events') || (bool) $matchId;

        try {
            // ==================== PASO 1: Buscar preguntas ====================
            $this->info("\n📋 PASO 1: Buscando preguntas a verificar...");

            $query = Question::query();

            // Si se especifica match-id, filtrar por ese partido
            if ($matchId) {
                $query->where('match_id', $matchId);
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

            if ($hydrateEvents) {
                $this->info("\n🛰  PASO 1B: Hidratando eventos faltantes antes de verificar...");
                $matches = $questions
                    ->pluck('football_match')
                    ->filter()
                    ->unique(fn ($match) => $match->id);

                if ($matches->isEmpty()) {
                    $this->line('   No hay partidos asociados para hidratar eventos.');
                } else {
                    $hydrationStats = $this->hydrateMissingEvents($matches, $force);
                    $this->line("   Partidos con request: {$hydrationStats['candidates']}");
                    $this->line("   Eventos actualizados: {$hydrationStats['updated']}");
                    $this->line("   Fallos al hidratar: {$hydrationStats['failed']}");
                }
            }

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

                            /**
                             * Intentar enriquecer partidos que no tienen eventos estructurados.
                             */
                            private function hydrateMissingEvents(Collection $matches, bool $forceRefresh): array
                            {
                                $stats = [
                                    'candidates' => 0,
                                    'updated' => 0,
                                    'failed' => 0,
                                ];

                                foreach ($matches as $match) {
                                    if (!$this->needsEventHydration($match)) {
                                        continue;
                                    }

                                    $stats['candidates']++;

                                    try {
                                        $details = $this->geminiService->getDetailedMatchData(
                                            $match->home_team,
                                            $match->away_team,
                                            $match->date ?? $match->match_date,
                                            $match->league,
                                            $forceRefresh
                                        );

                                        if (!$details || empty($details['events'])) {
                                            $stats['failed']++;
                                            continue;
                                        }

                                        $this->updateMatchWithDetails($match, $details);
                                        $stats['updated']++;

                                    } catch (\Throwable $e) {
                                        $stats['failed']++;
                                        Log::error('Error hidratando eventos desde VerifyQuestionAnswers', [
                                            'match_id' => $match->id,
                                            'error' => $e->getMessage()
                                        ]);
                                    }
                                }

                                return $stats;
                            }

                            private function needsEventHydration(?FootballMatch $match): bool
                            {
                                if (!$match || !$match->events) {
                                    return true;
                                }

                                $events = $match->events;

                                if (is_string($events)) {
                                    $decoded = json_decode($events, true);
                                    if (json_last_error() === JSON_ERROR_NONE) {
                                        $events = $decoded;
                                    }
                                }

                                if (!is_array($events) || empty($events)) {
                                    return true;
                                }

                                $first = $events[0];
                                return !(is_array($first) && isset($first['type'], $first['team']));
                            }

                            private function updateMatchWithDetails(FootballMatch $match, array $details): void
                            {
                                $statistics = $this->mergeStatistics($match, [
                                    'source' => 'Gemini (web search - VERIFIED)',
                                    'verified' => true,
                                    'verification_method' => 'manual_hydration',
                                    'has_detailed_events' => true,
                                    'detailed_event_count' => isset($details['events']) ? count($details['events']) : 0,
                                    'first_goal_scorer' => $details['first_goal_scorer'] ?? null,
                                    'last_goal_scorer' => $details['last_goal_scorer'] ?? null,
                                    'total_yellow_cards' => $details['total_yellow_cards'] ?? null,
                                    'total_red_cards' => $details['total_red_cards'] ?? null,
                                    'total_own_goals' => $details['total_own_goals'] ?? null,
                                    'total_penalty_goals' => $details['total_penalty_goals'] ?? null,
                                    'home_possession' => $details['home_possession'] ?? null,
                                    'away_possession' => $details['away_possession'] ?? null,
                                    'enriched_at' => now()->toIso8601String(),
                                ]);

                                $match->update([
                                    'home_team_score' => $details['home_goals'] ?? $match->home_team_score,
                                    'away_team_score' => $details['away_goals'] ?? $match->away_team_score,
                                    'events' => isset($details['events']) ? json_encode($details['events']) : $match->events,
                                    'statistics' => json_encode($statistics),
                                ]);
                            }

                            private function mergeStatistics(FootballMatch $match, array $newData): array
                            {
                                $current = $match->statistics;

                                if (is_string($current)) {
                                    $decoded = json_decode($current, true);
                                    if (json_last_error() === JSON_ERROR_NONE) {
                                        $current = $decoded;
                                    }
                                }

                                if (!is_array($current)) {
                                    $current = [];
                                }

                                return array_merge($current, $newData);
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
