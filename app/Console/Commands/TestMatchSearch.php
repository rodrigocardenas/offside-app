<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FootballService;
use App\Models\FootballMatch;
use Carbon\Carbon;

class TestMatchSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches:test-search {match_id : ID del partido a buscar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba la búsqueda de un partido específico con la nueva lógica';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $matchId = $this->argument('match_id');

        $match = FootballMatch::find($matchId);
        if (!$match) {
            $this->error("Partido con ID $matchId no encontrado");
            return;
        }

        $this->info("Probando búsqueda para partido:");
        $this->info("ID: {$match->id}");
        $this->info("Equipos: {$match->home_team} vs {$match->away_team}");
        $this->info("Fecha: {$match->date}");
        $this->info("External ID: {$match->external_id}");
        $this->info("Status: {$match->status}");

        $footballService = new FootballService();

        // Probar la búsqueda con la nueva lógica
        $matchDate = $match->date ? $match->date->format('Y-m-d') : null;
        $fixtureId = $footballService->extraerFixtureIdDelExternalId($match->external_id, $matchDate);

        if ($fixtureId) {
            $this->info("✅ Fixture ID encontrado: $fixtureId");

            // Obtener los datos del fixture
            $fixture = $footballService->obtenerFixtureDirecto($fixtureId);
            if ($fixture) {
                $this->info("✅ Datos del fixture obtenidos:");
                $this->info("Fecha del fixture: " . ($fixture['fixture']['date'] ?? 'N/A'));
                $this->info("Status: " . ($fixture['fixture']['status']['long'] ?? 'N/A'));
                $this->info("Score: " . ($fixture['goals']['home'] ?? 'N/A') . " - " . ($fixture['goals']['away'] ?? 'N/A'));
            } else {
                $this->error("❌ No se pudieron obtener los datos del fixture");
            }
        } else {
            $this->error("❌ No se encontró el fixture ID");
        }
    }
}
