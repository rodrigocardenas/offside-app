<?php

namespace App\Console\Commands;

use App\Models\Team;
use Illuminate\Console\Command;

class PopulateMissingCrests extends Command
{
    protected $signature = 'teams:populate-crests {--limit=50}';
    protected $description = 'Populate missing crest_url for teams from manual database';

    public function handle()
    {
        $limit = $this->option('limit');
        $teams = Team::whereNull('crest_url')->limit($limit)->get();

        $this->info("Procesando {$teams->count()} equipos sin logos...\n");

        $updated = 0;
        $failed = 0;

        foreach ($teams as $team) {
            $this->output->write("Buscando logo para: {$team->name} ({$team->api_name})... ");

            $crestUrl = $this->getCrestForTeam($team);

            if ($crestUrl) {
                $team->update(['crest_url' => $crestUrl]);
                $this->info("✓ {$crestUrl}");
                $updated++;
            } else {
                $this->error("✗ No encontrado");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("=== RESUMEN ===");
        $this->info("Actualizados: {$updated}");
        $this->error("No encontrados: {$failed}");
        $this->info("Total: {$teams->count()}");
    }

    
    private function getCrestForTeam(Team $team): ?string
    {
        $crests = [
            'Real Madrid' => '/storage/logos/Real_Madrid.png',
            'Barcelona' => '/storage/logos/FC_Barcelona.png',
            'Atlético Madrid' => '/storage/logos/Atl__tico_Madrid.png',
            'Manchester United' => '/storage/logos/Manchester_United.png',
            'Manchester City' => '/storage/logos/Manchester_City.png',
            'Liverpool' => '/storage/logos/Liverpool.png',
            'Arsenal' => '/storage/logos/Arsenal.png',
            'Chelsea' => '/storage/logos/Chelsea.png',
            'Tottenham' => '/storage/logos/Tottenham.png',
            'West Ham' => '/storage/logos/West_Ham.png',
            'Brighton' => '/storage/logos/Brighton.png',
            'Crystal Palace' => '/storage/logos/Crystal_Palace.png',
            'Everton' => '/storage/logos/Everton.png',
            'Brentford' => '/storage/logos/Brentford.png',
            'Fulham' => '/storage/logos/Fulham.png',
            'Bournemouth' => '/storage/logos/Bournemouth.png',
            'Aston Villa' => '/storage/logos/Aston_Villa.png',
            'Juventus' => '/storage/logos/Juventus.png',
            'Inter' => '/storage/logos/Inter.png',
            'AC Milan' => '/storage/logos/AC_Milan.png',
            'Roma' => '/storage/logos/Roma.png',
            'Atalanta' => '/storage/logos/Atalanta.png',
            'Bayern Munich' => '/storage/logos/Bayern_Munich.png',
            'Borussia Dortmund' => '/storage/logos/Borussia_Dortmund.png',
            'Paris Saint-Germain' => '/storage/logos/PSG.png',
            'Ajax' => '/storage/logos/Ajax.png',
            'PSV' => '/storage/logos/PSV.png',
        ];

        return $crests[$team->name] ?? $crests[$team->api_name] ?? null;
    }
}
