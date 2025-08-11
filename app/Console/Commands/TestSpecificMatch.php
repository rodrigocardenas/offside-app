<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FootballService;
use Carbon\Carbon;

class TestSpecificMatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches:test-specific {home_team} {away_team} {date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba la búsqueda de un partido específico con equipos y fecha';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $homeTeam = $this->argument('home_team');
        $awayTeam = $this->argument('away_team');
        $date = $this->argument('date');

        $this->info("Probando búsqueda para: $homeTeam vs $awayTeam en fecha $date");

        $footballService = new FootballService();

        // Probar con liga colombiana
        $competition = 'liga-colombia';
        $season = 2025;

        $this->info("Buscando en liga colombiana (temporada $season)...");

        // Probar primero con filtros de fecha
        $fixtureId = $footballService->buscarFixtureId($competition, $season, $homeTeam, $awayTeam, $date);

        if ($fixtureId) {
            $this->info("✅ Fixture encontrado con filtros de fecha: $fixtureId");
        } else {
            $this->info("❌ No se encontró con filtros de fecha, probando sin filtros...");

            // Probar sin filtros de fecha
            $fixtureId = $footballService->buscarFixtureIdLatinoamericano($competition, $season, $homeTeam, $awayTeam);

            if ($fixtureId) {
                $this->info("✅ Fixture encontrado sin filtros de fecha: $fixtureId");
            } else {
                $this->error("❌ No se encontró el fixture en ninguna búsqueda");
                return;
            }
        }

        // Obtener los datos del fixture
        $fixture = $footballService->obtenerFixtureDirecto($fixtureId);
        if ($fixture) {
            $this->info("✅ Datos del fixture obtenidos:");
            $this->info("Fecha del fixture: " . ($fixture['fixture']['date'] ?? 'N/A'));
            $this->info("Status: " . ($fixture['fixture']['status']['long'] ?? 'N/A'));
            $this->info("Score: " . ($fixture['goals']['home'] ?? 'N/A') . " - " . ($fixture['goals']['away'] ?? 'N/A'));
            $this->info("Equipos: " . ($fixture['teams']['home']['name'] ?? 'N/A') . " vs " . ($fixture['teams']['away']['name'] ?? 'N/A'));
        } else {
            $this->error("❌ No se pudieron obtener los datos del fixture");
        }
    }
}
