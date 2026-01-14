<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Question;
use App\Models\FootballMatch;
use Illuminate\Support\Facades\Log;

class LinkQuestionsToMatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questions:link-to-matches
                            {--dry-run : Mostrar qué se haría sin hacer cambios}
                            {--force : Forzar relinking incluso si ya tiene match_id}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Asociar preguntas a partidos usando extracción del título';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->info('║ Asociando Preguntas a Partidos                                ║');
        $this->info('╚═══════════════════════════════════════════════════════════════╝');

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        try {
            // ==================== PASO 1: Buscar preguntas ====================
            $this->info("\n📋 PASO 1: Buscando preguntas a asociar...");

            $query = Question::query();

            if (!$force) {
                $query->whereNull('match_id');
                $this->line("   Filtro: Sin match_id asignado (match_id = NULL)");
            } else {
                $this->line("   Filtro: Todas (forzar reasociación)");
            }

            $questions = $query->get();

            if ($questions->isEmpty()) {
                $this->warn("❌ No hay preguntas para asociar");
                return 0;
            }

            $this->info("✅ Encontradas {$questions->count()} preguntas");

            // ==================== PASO 2: Extraer equipos de títulos ====================
            $this->info("\n🔍 PASO 2: Extrayendo equipos y buscando partidos...\n");

            $progressBar = $this->output->createProgressBar($questions->count());
            $progressBar->start();

            $linkedCount = 0;
            $failedCount = 0;
            $updates = [];

            foreach ($questions as $idx => $question) {
                if ($idx % 5 === 0) {
                    $this->line("Procesando pregunta {$idx}/{$questions->count()}...");
                }

                try {
                    // Extraer nombres de equipos del título
                    $teams = $this->extractTeamsFromTitle($question->title);

                    if (!$teams || count($teams) < 2) {
                        $failedCount++;
                        continue;
                    }

                    [$team1, $team2] = $teams;

                    // Buscar partido con esos equipos
                    $match = FootballMatch::where(function ($query) use ($team1, $team2) {
                        $query->where(function ($q) use ($team1, $team2) {
                            $q->where('home_team', 'like', "%{$team1}%")
                              ->where('away_team', 'like', "%{$team2}%");
                        })->orWhere(function ($q) use ($team1, $team2) {
                            $q->where('home_team', 'like', "%{$team2}%")
                              ->where('away_team', 'like', "%{$team1}%");
                        });
                    })
                    ->orderByDesc('date') // Tomar el más reciente
                    ->first();

                    if (!$match) {
                        $failedCount++;
                        continue;
                    }

                    // Registrar update
                    $updates[] = [
                        'question_id' => $question->id,
                        'question_title' => $question->title,
                        'match_id' => $match->id,
                        'match_teams' => "{$match->home_team} vs {$match->away_team}",
                        'match_status' => $match->status
                    ];

                    if (!$dryRun) {
                        $question->match_id = $match->id;
                        $question->save();
                    }

                    $linkedCount++;

                } catch (\Exception $e) {
                    $failedCount++;
                    Log::warning("Error linking question to match", [
                        'question_id' => $question->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->line("✅ Procesamiento completado");

            // ==================== PASO 3: Mostrar resultados ====================
            $this->info("\n\n" . str_repeat("═", 70));
            $this->info("✅ PROCESO COMPLETADO");
            $this->info(str_repeat("═", 70));

            $this->line("Resultados:");
            $this->line("  ├─ Asociadas: {$linkedCount} ✅");
            $this->line("  └─ Fallidas: {$failedCount} ❌");

            if ($dryRun) {
                $this->line("\n⚠️  DRY RUN ACTIVO - Sin cambios realizados");
            }

            // Mostrar detalles
            if (!empty($updates)) {
                $this->info("\n📋 DETALLES DE ASOCIACIONES:");
                foreach (array_slice($updates, 0, 10) as $update) {
                    $this->line("  ├─ Q{$update['question_id']}: {$update['question_title']}");
                    $this->line("  │  └─ Match {$update['match_id']}: {$update['match_teams']} ({$update['match_status']})");
                }
                if (count($updates) > 10) {
                    $this->line("  └─ ... y " . (count($updates) - 10) . " más");
                }
            }

            Log::info("Linking questions to matches completed", [
                'total_processed' => $questions->count(),
                'linked' => $linkedCount,
                'failed' => $failedCount,
                'dry_run' => $dryRun
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error crítico: " . $e->getMessage());
            Log::error("Error en LinkQuestionsToMatches command", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Extraer nombres de equipos del título
     * Busca patrones como "Equipo1 vs Equipo2" después de "partido"
     */
    private function extractTeamsFromTitle(string $title): ?array
    {
        // Enfocarse en lo que viene después de "partido" o "en"
        // Ejemplo: "en el partido Real Madrid vs Barcelona?"
        
        // Patrón principal: después de "partido"
        if (preg_match('/partido\s+([A-Z][a-záéíóúñ\s]+?)\s+vs\s+([A-Z][a-záéíóúñ\s]+?)(?:\?|$|[\s,])/i', $title, $matches)) {
            $team1 = trim($matches[1]);
            $team2 = trim($matches[2]);
            
            if (strlen($team1) > 2 && strlen($team2) > 2) {
                return [$team1, $team2];
            }
        }
        
        // Patrón alternativo: busca "en" antes del nombre (e.g., "en Real Madrid vs Barcelona")
        if (preg_match('/en\s+([A-Z][a-záéíóúñ\s]+?)\s+vs\s+([A-Z][a-záéíóúñ\s]+?)(?:\?|$|[\s,])/i', $title, $matches)) {
            $team1 = trim($matches[1]);
            $team2 = trim($matches[2]);
            
            if (strlen($team1) > 2 && strlen($team2) > 2) {
                return [$team1, $team2];
            }
        }
        
        // Patrón genérico final: cualquier "vs" en el título
        if (preg_match('/([A-Z][a-záéíóúñ\s]+?)\s+vs\s+([A-Z][a-záéíóúñ\s]+?)(?:\?|$|[\s,])/i', $title, $matches)) {
            $team1 = trim($matches[1]);
            $team2 = trim($matches[2]);
            
            if (strlen($team1) > 2 && strlen($team2) > 2) {
                return [$team1, $team2];
            }
        }

        return null;
    }
}
