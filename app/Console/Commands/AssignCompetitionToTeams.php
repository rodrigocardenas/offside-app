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
            // La Liga y Champions League
            'Real Madrid CF' => [1, 3],
            'FC Barcelona' => [1, 3],
            'Atlético Madrid' => [1, 3],
            'Real Sociedad' => [1, 3],
            'Athletic' => [1, 3],
            'Villarreal CF' => [1, 3],
            'Valencia CF' => [1, 3],
            'Girona FC' => [1, 3],
            'Sevilla FC' => [1, 3],
            'Real Betis' => [1, 3],
            'UD Las Palmas' => [1, 3],
            'CA Osasuna' => [1, 3],
            'RC Celta' => [1, 3],
            'RCD Espanyol' => [1, 3],
            'RCD Mallorca' => [1, 3],
            'Getafe CF' => [1, 3],
            'Alavés' => [1, 3],
            'Rayo Vallecano' => [1, 3],
            'Real Valladolid' => [1, 3],

            // Premier League y Champions League
            'Manchester City' => [2, 3],
            'Liverpool FC' => [2, 3],
            'Newcastle Utd.' => [2, 3],
            'Aston Villa' => [2, 3],
            'Brighton' => [2, 3],
            'West Ham' => [2, 3],
            'Nottm Forest' => [2, 3],
            'Crystal Palace' => [2, 3],
            'Bournemouth' => [2, 3],
            'Brentford' => [2, 3],
            'Wolverhampton' => [2, 3],
            'Everton' => [2, 3],
            'Fulham' => [2, 3],
            'Ipswich' => [2, 3],
            'Southampton' => [2, 3],
            'Leicester City' => [2, 3],

            // Solo Premier League
            'Manchester United' => [2],
            'Chelsea' => [2],
            'Arsenal' => [2],
            'Tottenham' => [2],
            'West Brom' => [2],
            'Watford' => [2],

            // Solo Champions League
            'Bayern Múnich' => [3],
            'PSG' => [3],
            'Juventus' => [3],
            'Inter' => [3],
            'AC Milan' => [3],
            'Borussia Dortmund' => [3],
            'RB Leipzig' => [3],
            'Porto' => [3],
            'Benfica' => [3],
            'Ajax' => [3],
            'PSV' => [3],
            'Club Brujas' => [3],
            'Shakhtar D.' => [3],
            'Dinamo Zagreb' => [3],
            'Estrella Roja' => [3],
            'Sparta Praga' => [3],
            'Young Boys' => [3],
            'RB Salzburgo' => [3],
            'Slo. Bratislava' => [3],
            'Sturm Graz' => [3],
            'Bolonia' => [3],
            'Feyenoord' => [3],
            'Mónaco' => [3],
            'Stade Brestois' => [3],
            'Sporting Lisboa' => [3],
            'Celtic FC' => [3],
            'Leverkusen' => [3],
            'Atalanta' => [3],
            'Lille' => [3],
        ];

        // Equipos del Mundial de Clubes (competition_id = 4)
        $clubWorldCupTeams = [
            'Manchester City' => 'Inglaterra',
            'Real Madrid CF' => 'España',
            'Bayern Múnich' => 'Alemania',
            'París Saint-Germain' => 'Francia',
            'Chelsea' => 'Inglaterra',
            'Borussia Dortmund' => 'Alemania',
            'Inter' => 'Italia',
            'Porto' => 'Portugal',
            'Atlético Madrid' => 'España',
            'Benfica' => 'Portugal',
            'Juventus' => 'Italia',
            'RB Salzburgo' => 'Austria',
            'Flamengo' => 'Brasil',
            'Palmeiras' => 'Brasil',
            'River Plate' => 'Argentina',
            'Fluminense' => 'Brasil',
            'Boca Juniors' => 'Argentina',
            'Botafogo' => 'Brasil',
            'Al Hilal' => 'Arabia Saudí',
            'Ulsan HD' => 'Corea del Sur',
            'Urawa Red Diamonds' => 'Japón',
            'Al Ain' => 'Emiratos Árabes Unidos',
            'Al Ahly' => 'Egipto',
            'Wydad AC' => 'Marruecos',
            'Espérance de Tunis' => 'Túnez',
            'Mamelodi Sundowns' => 'Sudáfrica',
            'Monterrey' => 'México',
            'LAFC' => 'Estados Unidos',
            'Pachuca' => 'México',
            'Seattle Sounders FC' => 'Estados Unidos',
            'Auckland City' => 'Nueva Zelanda',
            'Inter Miami CF' => 'Estados Unidos',
        ];
        // Añadir el ID 4 a cada equipo del Mundial de Clubes
        foreach ($clubWorldCupTeams as $teamName => $country) {
            if (isset($competitionMap[$teamName])) {
                if (!in_array(4, $competitionMap[$teamName])) {
                    $competitionMap[$teamName][] = 4;
                }
            } else {
                $competitionMap[$teamName] = [4];
            }
        }

        $updated = 0;
        foreach ($competitionMap as $teamName => $competitionIds) {
            $team = Team::where('name', $teamName)->first();
            if ($team) {
                $team->competitions()->syncWithoutDetaching($competitionIds);
                $updated++;
            }
        }

        $this->info("Equipos actualizados: $updated");
        $this->info('¡Asignación completada!');
    }
}
