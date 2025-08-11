<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Services\FootballService;
use App\Services\FootballDataService;
use App\Models\Competition;

class RefreshMatchesCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches:refresh-cache {competition? : ID de la competencia específica}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresca el cache de partidos para obtener los datos más recientes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $competitionId = $this->argument('competition');

        if ($competitionId) {
            $this->refreshCompetitionMatches($competitionId);
        } else {
            $this->refreshAllCompetitions();
        }

        $this->info('Cache de partidos refrescado exitosamente.');
    }

    private function refreshCompetitionMatches($competitionId)
    {
        $this->info("Refrescando cache para competencia: $competitionId");

        // Limpiar cache específico
        $cacheKey = "matches_{$competitionId}";
        Cache::forget($cacheKey);

        // Forzar refresh usando FootballService
        $footballService = new FootballService();
        $matches = $footballService->getMatches($competitionId, true);

        $this->info("Partidos obtenidos: " . $matches->count());

        // Forzar refresh usando FootballDataService
        $footballDataService = new FootballDataService();
        $matchesData = $footballDataService->getMatches($competitionId, true);

        $this->info("Partidos obtenidos (FootballDataService): " . $matchesData->count());
    }

    private function refreshAllCompetitions()
    {
        $this->info('Refrescando cache para todas las competencias...');

        $competitions = Competition::all();

        foreach ($competitions as $competition) {
            $this->info("Procesando competencia: {$competition->name} (ID: {$competition->id})");
            $this->refreshCompetitionMatches($competition->id);
        }
    }
}
