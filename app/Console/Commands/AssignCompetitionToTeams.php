<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Team;

class AssignCompetitionToTeams extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:assign-competition';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asigna competition_id a los equipos basado en su nombre';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Asignando competencias a equipos...');

        // Mapeo de nombres de equipos a IDs de competencia
        $competitionMap = [
            // Equipos de la Champions League (competition_id = 1)
            'Real Madrid CF' => 1,
            'FC Barcelona' => 1,
            'Atlético Madrid' => 1,
            'Bayern Múnich' => 1,
            'Manchester City' => 1,
            'Liverpool FC' => 1,
            'PSG' => 1,
            'Juventus' => 1,
            'Inter' => 1,
            'AC Milan' => 1,
            'Borussia Dortmund' => 1,
            'RB Leipzig' => 1,
            'Porto' => 1,
            'Benfica' => 1,
            'Ajax' => 1,
            'PSV' => 1,
            'Club Brujas' => 1,
            'Shakhtar D.' => 1,
            'Dinamo Zagreb' => 1,
            'Estrella Roja' => 1,
            'Sparta Praga' => 1,
            'Young Boys' => 1,
            'RB Salzburgo' => 1,
            'Slo. Bratislava' => 1,
            'Sturm Graz' => 1,
            'Bolonia' => 1,
            'Feyenoord' => 1,
            'Mónaco' => 1,
            'Stade Brestois' => 1,
            'Sporting Lisboa' => 1,
            'Celtic FC' => 1,
            'Leverkusen' => 1,
            'Atalanta' => 1,
            'Lille' => 1,
            'Newcastle Utd.' => 1,
            'Aston Villa' => 1,
            'Brighton' => 1,
            'West Ham' => 1,
            'Nottm Forest' => 1,
            'Crystal Palace' => 1,
            'Bournemouth' => 1,
            'Brentford' => 1,
            'Wolverhampton' => 1,
            'Everton' => 1,
            'Fulham' => 1,
            'Ipswich' => 1,
            'Southampton' => 1,
            'Leicester City' => 1,
            'Villarreal CF' => 1,
            'Valencia CF' => 1,
            'Girona FC' => 1,
            'Sevilla FC' => 1,
            'Real Betis' => 1,
            'UD Las Palmas' => 1,
            'CA Osasuna' => 1,
            'RC Celta' => 1,
            'RCD Espanyol' => 1,
            'RCD Mallorca' => 1,
            'Getafe CF' => 1,
            'Alavés' => 1,
            'Rayo Vallecano' => 1,
            'Real Valladolid' => 1,
            'Real Sociedad' => 1,
            'Athletic' => 1,
            // Equipos de La Liga (competition_id = 2)
            'Real Madrid CF' => 2,
            'FC Barcelona' => 2,
            'Atlético Madrid' => 2,
            'Real Sociedad' => 2,
            'Athletic' => 2,
            'Villarreal CF' => 2,
            'Valencia CF' => 2,
            'Girona FC' => 2,
            'Sevilla FC' => 2,
            'Real Betis' => 2,
            'UD Las Palmas' => 2,
            'CA Osasuna' => 2,
            'RC Celta' => 2,
            'RCD Espanyol' => 2,
            'RCD Mallorca' => 2,
            'Getafe CF' => 2,
            'Alavés' => 2,
            'Rayo Vallecano' => 2,
            'Real Valladolid' => 2,
            // Equipos de la Premier League (competition_id = 3)
            'Manchester City' => 3,
            'Liverpool FC' => 3,
            'Newcastle Utd.' => 3,
            'Aston Villa' => 3,
            'Brighton' => 3,
            'West Ham' => 3,
            'Nottm Forest' => 3,
            'Crystal Palace' => 3,
            'Bournemouth' => 3,
            'Brentford' => 3,
            'Wolverhampton' => 3,
            'Everton' => 3,
            'Fulham' => 3,
            'Ipswich' => 3,
            'Southampton' => 3,
            'Leicester City' => 3,
            'Manchester United' => 3,
            'Chelsea' => 3,
            'Arsenal' => 3,
            'Tottenham' => 3,
            'West Brom' => 3,
            'Watford' => 3,

        ];

        $updated = 0;
        foreach ($competitionMap as $teamName => $competitionId) {
            $count = Team::where('name', $teamName)->update(['competition_id' => $competitionId]);
            $updated += $count;
        }

        $this->info("Equipos actualizados: $updated");
        $this->info('¡Asignación completada!');
    }
}
