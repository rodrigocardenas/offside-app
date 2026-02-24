<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use App\Models\Competition;

class LinkMatchesWithCompetitions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:link-matches-with-competitions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link existing football matches with preexisting competitions based on league field';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Mapear códigos de liga a IDs de competición preexistentes
        $leagueMap = [
            // Competiciones principales
            'PL' => 2,           // Premier League
            'premier' => 2,      // Premier League
            'PD' => 1,           // La Liga (PD = Primera División)
            'laliga' => 1,       // La Liga
            'la-liga' => 1,      // La Liga
            'SA' => 4,           // Serie A
            'serie-a' => 4,      // Serie A
            'BL1' => 5,          // Bundesliga
            'bundesliga' => 5,   // Bundesliga
            'FL1' => 3,          // Ligue 1 (Champions League como fallback)
            'ligue-1' => 3,      // Ligue 1
            'CL' => 3,           // Champions League
            'champions' => 3,    // Champions League
            'ELC' => 3,          // Europa League (Champions League como fallback)

            // Copas
            'fa-cup' => 6,       // FA Cup
            'FA-CUP' => 6,       // FA Cup
            'league-cup' => 7,   // League Cup
            'LEAGUE-CUP' => 7,   // League Cup
            'copa-del-rey' => 8, // Copa del Rey
            'COPA-DEL-REY' => 8, // Copa del Rey
            'copa del rey' => 8, // Copa del Rey
        ];

        $updated = 0;
        $notFound = [];

        foreach ($leagueMap as $league => $competitionId) {
            $count = FootballMatch::where('league', $league)
                ->whereNull('competition_id')
                ->update(['competition_id' => $competitionId]);

            if ($count > 0) {
                $competition = Competition::find($competitionId);
                $this->info("✓ Linked $count matches for league: $league → " . $competition->name);
                $updated += $count;
            }
        }

        $this->info("\n✅ Total matches linked: $updated");

        // Mostrar partidos sin competición
        $unmappedCount = FootballMatch::whereNull('competition_id')->count();
        if ($unmappedCount > 0) {
            $this->warn("\n⚠️  $unmappedCount matches still without competition_id");
            $unmappedLeagues = FootballMatch::whereNull('competition_id')
                ->distinct('league')
                ->pluck('league');
            $this->line("Unmapped leagues: " . implode(', ', $unmappedLeagues->toArray()));
        }

        return Command::SUCCESS;
    }
}

