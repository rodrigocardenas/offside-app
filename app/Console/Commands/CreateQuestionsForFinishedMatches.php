<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Support\Facades\Log;

class CreateQuestionsForFinishedMatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questions:create-for-finished
                            {--match-id= : Crear solo para un partido específico}
                            {--limit=10 : Máximo número de partidos}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Crear preguntas automáticamente para partidos finalizados con datos verificados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->info('║ Creando Preguntas para Partidos Finalizados                   ║');
        $this->info('╚═══════════════════════════════════════════════════════════════╝');

        $matchId = $this->option('match-id');
        $limit = (int) $this->option('limit');

        try {
            // ==================== PASO 0: Obtener grupo ====================
            $this->info("\n🔧 PASO 0: Verificando grupo...");

            $group = \App\Models\Group::first();

            if (!$group) {
                $this->error("❌ No hay ningún grupo en la base de datos. Crea uno desde la interfaz web primero.");
                return 1;
            }

            $this->line("   Grupo: {$group->name} (ID: {$group->id})");

            // ==================== PASO 1: Buscar partidos ====================
            $this->info("\n📋 PASO 1: Buscando partidos finalizados con datos verificados...");

            $query = FootballMatch::where('status', 'Match Finished');

            if ($matchId) {
                $query->where('id', $matchId);
                $this->line("   Filtro: Match ID = {$matchId}");
            } else {
                $this->line("   Filtro: Todos los Match Finished");
            }

            $matches = $query
                ->whereDoesntHave('questions') // Sin preguntas aún
                ->limit($limit)
                ->get();

            if ($matches->isEmpty()) {
                $this->warn("❌ No hay partidos finalizados sin preguntas");
                return 0;
            }

            $this->info("✅ Encontrados {$matches->count()} partidos");

            // ==================== PASO 2: Crear preguntas ====================
            $this->info("\n📊 PASO 2: Creando preguntas...\n");

            $createdCount = 0;
            $failedCount = 0;

            foreach ($matches as $match) {
                $this->line("Procesando: {$match->home_team} vs {$match->away_team} (ID: {$match->id})...");

                try {
                    // Validar que tenga datos verificados
                    $stats = is_string($match->statistics) 
                        ? json_decode($match->statistics, true) 
                        : $match->statistics;

                    if (!is_array($stats) || stripos($stats['source'] ?? '', 'fallback') !== false) {
                        $this->line("  ⚠️ Sin datos verificados - saltando");
                        $failedCount++;
                        continue;
                    }

                    // Determinar ganador para pregunta de resultado
                    $winner = null;
                    if ($match->home_team_score > $match->away_team_score) {
                        $winner = $match->home_team;
                    } elseif ($match->away_team_score > $match->home_team_score) {
                        $winner = $match->away_team;
                    } else {
                        $winner = 'Empate';
                    }

                    // Crear pregunta 1: ¿Cuál fue el resultado?
                    $question1 = Question::create([
                        'title' => "¿Cuál fue el resultado del partido {$match->home_team} vs {$match->away_team}?",
                        'description' => "{$match->home_team} vs {$match->away_team}",
                        'type' => 'multiple_choice',
                        'category' => 'predictive',
                        'points' => 300,
                        'group_id' => $group->id,
                        'match_id' => $match->id,
                        'available_until' => now()->addDays(7)
                    ]);

                    // Opciones para pregunta 1
                    QuestionOption::create([
                        'question_id' => $question1->id,
                        'text' => $match->home_team,
                        'is_correct' => ($winner === $match->home_team)
                    ]);
                    QuestionOption::create([
                        'question_id' => $question1->id,
                        'text' => $match->away_team,
                        'is_correct' => ($winner === $match->away_team)
                    ]);
                    QuestionOption::create([
                        'question_id' => $question1->id,
                        'text' => 'Empate',
                        'is_correct' => ($winner === 'Empate')
                    ]);

                    // Crear pregunta 2: ¿Ambos equipos anotaron?
                    $bothScored = ($match->home_team_score > 0 && $match->away_team_score > 0);
                    $question2 = Question::create([
                        'title' => "¿Ambos equipos anotaron en {$match->home_team} vs {$match->away_team}?",
                        'description' => "{$match->home_team} vs {$match->away_team}",
                        'type' => 'multiple_choice',
                        'category' => 'predictive',
                        'points' => 300,
                        'group_id' => $group->id,
                        'match_id' => $match->id,
                        'available_until' => now()->addDays(7)
                    ]);

                    QuestionOption::create([
                        'question_id' => $question2->id,
                        'text' => 'Sí, ambos anotaron',
                        'is_correct' => $bothScored
                    ]);
                    QuestionOption::create([
                        'question_id' => $question2->id,
                        'text' => 'No, al menos uno no anotó',
                        'is_correct' => !$bothScored
                    ]);

                    // Crear pregunta 3: ¿Más o menos de 2.5 goles?
                    $totalGoals = $match->home_team_score + $match->away_team_score;
                    $question3 = Question::create([
                        'title' => "¿Más de 2.5 goles en {$match->home_team} vs {$match->away_team}?",
                        'description' => "{$match->home_team} vs {$match->away_team}",
                        'type' => 'multiple_choice',
                        'category' => 'predictive',
                        'points' => 300,
                        'group_id' => $group->id,
                        'match_id' => $match->id,
                        'available_until' => now()->addDays(7)
                    ]);

                    QuestionOption::create([
                        'question_id' => $question3->id,
                        'text' => 'Más de 2.5 goles',
                        'is_correct' => ($totalGoals > 2.5)
                    ]);
                    QuestionOption::create([
                        'question_id' => $question3->id,
                        'text' => '2.5 goles o menos',
                        'is_correct' => ($totalGoals <= 2.5)
                    ]);

                    // Marcar preguntas como verificadas
                    Question::whereIn('id', [$question1->id, $question2->id, $question3->id])
                        ->update(['result_verified_at' => now()]);

                    $this->line("  ✅ 3 preguntas creadas");
                    $createdCount += 3;

                } catch (\Exception $e) {
                    $this->line("  ❌ Error: " . $e->getMessage());
                    $failedCount++;
                    Log::error("Error creating questions for match", [
                        'match_id' => $match->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // ==================== PASO 3: Resumen ====================
            $this->info("\n" . str_repeat("═", 70));
            $this->info("✅ PROCESO COMPLETADO");
            $this->info(str_repeat("═", 70));

            $this->line("Resultados:");
            $this->line("  ├─ Preguntas creadas: {$createdCount} ✅");
            $this->line("  └─ Partidos fallidos: {$failedCount} ❌");

            Log::info("Creating questions for finished matches completed", [
                'total_matches' => $matches->count(),
                'questions_created' => $createdCount,
                'failed' => $failedCount
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error crítico: " . $e->getMessage());
            Log::error("Error en CreateQuestionsForFinishedMatches command", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
