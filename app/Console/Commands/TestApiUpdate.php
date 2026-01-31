<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use App\Services\FootballService;

class TestApiUpdate extends Command
{
    protected $signature = 'app:test-api-update {matchId}';
    protected $description = 'Probar updateMatchFromApi en un partido específico';

    public function handle()
    {
        $matchId = $this->argument('matchId');
        $match = FootballMatch::find($matchId);

        if (!$match) {
            $this->error("Partido no encontrado");
            return;
        }

        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("🧪 PROBANDO updateMatchFromApi() para partido {$matchId}");
        $this->info("═══════════════════════════════════════════════════════════");

        $this->info("Datos actuales:");
        $this->line("  Home: {$match->home_team} | Away: {$match->away_team}");
        $this->line("  Score: {$match->score} | Status: {$match->status}");
        $this->line("  External ID: {$match->external_id}");
        $this->line("  Events: " . (strlen($match->events) > 0 ? "✓ (" . strlen($match->events) . " chars)" : "✗ vacío"));
        $this->line("  Statistics: " . (strlen($match->statistics) > 0 ? "✓ (" . strlen($match->statistics) . " chars)" : "✗ vacío"));

        $this->info("\n➡️  Llamando updateMatchFromApi()...\n");

        $footballService = app(FootballService::class);
        $result = $footballService->updateMatchFromApi($matchId);

        if ($result) {
            $this->info("\n✅ Actualización exitosa, refresco de datos...");
            $match->refresh();

            $this->info("\nDatos DESPUÉS de actualizar:");
            $this->line("  Home: {$match->home_team} | Away: {$match->away_team}");
            $this->line("  Score: {$match->score} | Status: {$match->status}");
            $this->line("  External ID: {$match->external_id}");
            $this->line("  Events: " . (strlen($match->events) > 0 ? "✓ (" . strlen($match->events) . " chars)" : "✗ vacío"));

            if ($match->events) {
                $decoded = json_decode($match->events, true);
                $this->line("    → " . count($decoded) . " eventos obtenidos");

                // Verificar que hasStructuredEvents lo reconocería
                if (is_array($decoded) && !empty($decoded)) {
                    $first = $decoded[0];
                    $hasTypeAndTeam = is_array($first) && isset($first['type'], $first['team']);
                    $this->line("    → hasStructuredEvents() retornaría: " . ($hasTypeAndTeam ? "✓ TRUE" : "✗ FALSE"));
                    if ($hasTypeAndTeam) {
                        $this->line("       Estructura correcta: type={$first['type']}, team={$first['team']}");
                    }
                }
            }

            $this->line("  Statistics: " . (strlen($match->statistics) > 0 ? "✓ (" . strlen($match->statistics) . " chars)" : "✗ vacío"));

            if ($match->statistics) {
                $decoded = json_decode($match->statistics, true);
                if ($decoded && isset($decoded['source'])) {
                    $this->line("    → Source: " . $decoded['source']);
                }
            }
        } else {
            $this->error("\n❌ updateMatchFromApi() retornó null. Ver logs para detalles.");
        }
    }
}
