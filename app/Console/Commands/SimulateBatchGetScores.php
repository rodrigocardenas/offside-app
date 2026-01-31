<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use App\Services\FootballService;
use App\Services\GeminiBatchService;

class SimulateBatchGetScores extends Command
{
    protected $signature = 'app:simulate-batch-get-scores {matchIds?}';
    protected $description = 'Simular BatchGetScoresJob con IDs específicos';

    public function handle()
    {
        $matchIdsArg = $this->argument('matchIds') ?? '484,485,486';
        $matchIds = array_map('intval', explode(',', $matchIdsArg));

        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("🧪 SIMULANDO BatchGetScoresJob");
        $this->info("═══════════════════════════════════════════════════════════");

        $footballService = app(FootballService::class);

        $matches = FootballMatch::whereIn('id', $matchIds)->get();
        $this->info("Partidos cargados: " . $matches->count());

        foreach ($matches as $match) {
            $this->info("\n→ Procesando {$match->id}: {$match->home_team} vs {$match->away_team}");

            // Simular lo que hace BatchGetScoresJob
            $updatedMatch = $footballService->updateMatchFromApi($match->id);

            if ($updatedMatch) {
                $this->info("  ✅ Actualizado desde API Football");
                $this->line("     Score: {$updatedMatch->score}");
                $this->line("     Events: " . (strlen($updatedMatch->events) > 0 ? "✓" : "✗"));

                // Verificar que hasValidScore lo reconoce
                $hasValid = $updatedMatch->home_team_score !== null && $updatedMatch->away_team_score !== null;
                $this->line("     Has valid score: " . ($hasValid ? "✓" : "✗"));
            } else {
                $this->warn("  ❌ No se pudo actualizar");
            }
        }

        $this->info("\n═══════════════════════════════════════════════════════════");
        $this->info("Ahora verificando QUÉ VE BatchExtractEventsJob...");
        $this->info("═══════════════════════════════════════════════════════════");

        // Recargar desde BD (como lo hace BatchExtractEventsJob)
        $reloadedMatches = FootballMatch::whereIn('id', $matchIds)->get();

        foreach ($reloadedMatches as $match) {
            $this->info("\n→ Partido {$match->id}: {$match->home_team} vs {$match->away_team}");
            $this->line("  Events en BD: " . (strlen($match->events) > 0 ? strlen($match->events) . " chars" : "✗ vacío"));

            if ($match->events) {
                $decoded = json_decode($match->events, true);
                $this->line("  Eventos decodificados: " . count($decoded));

                // Simular hasStructuredEvents
                if (!empty($decoded)) {
                    $first = $decoded[0];
                    $hasStructured = is_array($first) && isset($first['type'], $first['team']);
                    $this->line("  hasStructuredEvents: " . ($hasStructured ? "✓ TRUE (no necesita Gemini)" : "✗ FALSE (intentará Gemini)"));
                }
            }
        }
    }
}
