<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Team;

class MarkFeaturedTeams extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mark-featured-teams';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark featured teams (big clubs) for each competition';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Mapeo de equipos destacados por competencia y nombre de equipo
        $featuredTeams = [
            // La Liga (1)
            'Real Madrid' => 1,
            'Barcelona' => 1,
            'Atlético Madrid' => 1,
            'Sevilla' => 1,
            'Valencia' => 1,

            // Premier League (2)
            'Manchester United' => 2,
            'Manchester City' => 2,
            'Liverpool' => 2,
            'Arsenal' => 2,
            'Chelsea' => 2,
            'Tottenham Hotspur' => 2,
            'Manchester Utd' => 2,
            'Man United' => 2,

            // Champions League (3)
            'Bayern Munich' => 3,
            'Paris Saint-Germain' => 3,
            'AC Milan' => 3,
            'Inter Milan' => 3,

            // Serie A (4)
            'Juventus' => 4,
            'AC Milan' => 4,
            'Inter Milan' => 4,
            'Roma' => 4,
            'Napoli' => 4,
        ];

        // Resetear todos los equipos a no destacados
        Team::query()->update(['is_featured' => false]);

        // Marcar equipos destacados
        $updated = 0;
        foreach ($featuredTeams as $teamName => $competitionId) {
            $count = Team::where('name', 'LIKE', "%$teamName%")
                ->update(['is_featured' => true]);
            $updated += $count;
        }

        $this->info("✓ Equipos destacados marcados: $updated");
    }
}
