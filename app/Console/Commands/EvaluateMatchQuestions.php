<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\FootballMatch;
use App\Services\QuestionEvaluationService;
use Illuminate\Console\Command;

class EvaluateMatchQuestions extends Command
{
    protected $signature = 'app:evaluate-match-questions {--match-id=2003} {--force=false} {--group-id=}';
    protected $description = 'Evalúa todas las preguntas de un match usando el servicio de evaluación';

    public function handle()
    {
        $matchId = $this->option('match-id');
        $force = (bool) $this->option('force');
        $groupId = $this->option('group-id');

        $match = FootballMatch::find($matchId);
        if (!$match) {
            $this->error("Match {$matchId} no encontrado");
            return;
        }

        $this->info("═══════════════════════════════════════════════════════════════");
        $this->info("EVALUANDO PREGUNTAS - MATCH {$matchId}");
        $this->info("Equipos: {$match->home_team} vs {$match->away_team}");
        $this->info("Resultado: {$match->home_team_score} - {$match->away_team_score}");
        $this->info("═══════════════════════════════════════════════════════════════\n");

        // Obtener preguntas
        $query = Question::where('match_id', $matchId);
        if ($groupId) {
            $query->where('group_id', $groupId);
        }
        $questions = $query->with('options', 'group')->get();

        if ($questions->isEmpty()) {
            $this->warn("No hay preguntas para este match");
            return;
        }

        $this->info("Preguntas encontradas: {$questions->count()}\n");

        $evaluationService = new QuestionEvaluationService();
        $processed = 0;
        $withOptions = 0;

        foreach ($questions as $question) {
            $this->line("\n📝 Pregunta {$question->id}: " . substr($question->title, 0, 80));

            // Evaluar usando el servicio
            $correctOptionIds = $this->evaluateQuestion($evaluationService, $question, $match);

            $processed++;

            if (!empty($correctOptionIds)) {
                $withOptions++;
                // Obtener los textos de las opciones correctas
                $correctTexts = $question->options
                    ->whereIn('id', $correctOptionIds)
                    ->pluck('text')
                    ->toArray();

                $this->info("  ✅ Opciones correctas: " . implode(" | ", $correctTexts));

                // Comparar con lo actual
                $currentCorrect = $question->options->where('is_correct', true)->pluck('id')->toArray();
                if ($currentCorrect !== $correctOptionIds) {
                    $this->warn("  ⚠️  DIFERENCIA: Actuales son " . implode(", ", $currentCorrect));
                    
                    if ($force) {
                        // Actualizar base de datos
                        $question->options()->update(['is_correct' => false]);
                        $question->options()
                            ->whereIn('id', $correctOptionIds)
                            ->update(['is_correct' => true]);
                        $this->info("  ✓ Actualizadas las opciones correctas");
                    }
                }
            } else {
                $this->error("  ❌ No se pudo evaluar");
            }
        }

        $this->info("\n" . str_repeat("═", 130));
        $this->info("RESUMEN:");
        $this->info("  Preguntas procesadas: {$processed}");
        $this->info("  Preguntas evaluadas: {$withOptions}");
        $this->info("  Tasa de evaluación: " . ($processed > 0 ? round(($withOptions / $processed) * 100) : 0) . "%");

        if ($force) {
            $this->info("\n✅ Base de datos actualizada. Ahora ejecuta:");
            $this->info("   php artisan answers:reevaluate --group={$questions->first()->group_id}");
        } else {
            $this->info("\nUsually: php artisan app:evaluate-match-questions --match-id={$matchId} --force=true");
        }
    }

    /**
     * Evalúa una pregunta individual
     */
    private function evaluateQuestion(QuestionEvaluationService $service, Question $question, FootballMatch $match): array
    {
        // Usar reflection para acceder al método privado evaluateQuestion
        $reflectionMethod = new \ReflectionMethod(QuestionEvaluationService::class, 'evaluateQuestion');
        $reflectionMethod->setAccessible(true);

        try {
            $result = $reflectionMethod->invoke($service, $question, $match);
            return is_array($result) ? $result : [];
        } catch (\Exception $e) {
            $this->error("  Error evaluando: " . $e->getMessage());
            return [];
        }
    }
}
