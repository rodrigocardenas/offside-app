<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Models\Competition;

class NationalTeamsSeeder extends Seeder
{
    public function run()
    {
        $competition = Competition::find(5); // Amistosos de Selecciones

        $nationalTeams = [
            [
                'name' => 'España',
                'short_name' => 'ESP',
                'tla' => 'ESP',
                'country' => 'España',
                'type' => 'national',
                'crest_url' => 'https://crests.football-data.org/esp.svg'
            ],
            [
                'name' => 'Argentina',
                'short_name' => 'ARG',
                'tla' => 'ARG',
                'country' => 'Argentina',
                'type' => 'national',
                'crest_url' => 'https://crests.football-data.org/arg.svg'
            ],
            [
                'name' => 'Brasil',
                'short_name' => 'BRA',
                'tla' => 'BRA',
                'country' => 'Brasil',
                'type' => 'national',
                'crest_url' => 'https://crests.football-data.org/bra.svg'
            ],
            [
                'name' => 'Francia',
                'short_name' => 'FRA',
                'tla' => 'FRA',
                'country' => 'Francia',
                'type' => 'national',
                'crest_url' => 'https://crests.football-data.org/fra.svg'
            ],
            [
                'name' => 'Alemania',
                'short_name' => 'GER',
                'tla' => 'GER',
                'country' => 'Alemania',
                'type' => 'national',
                'crest_url' => 'https://crests.football-data.org/ger.svg'
            ],
            [
                'name' => 'Italia',
                'short_name' => 'ITA',
                'tla' => 'ITA',
                'country' => 'Italia',
                'type' => 'national',
                'crest_url' => 'https://crests.football-data.org/ita.svg'
            ],
            [
                'name' => 'Inglaterra',
                'short_name' => 'ENG',
                'tla' => 'ENG',
                'country' => 'Inglaterra',
                'type' => 'national',
                'crest_url' => 'https://crests.football-data.org/eng.svg'
            ],
            [
                'name' => 'Portugal',
                'short_name' => 'POR',
                'tla' => 'POR',
                'country' => 'Portugal',
                'type' => 'national',
                'crest_url' => 'https://crests.football-data.org/por.svg'
            ],
            [
                'name' => 'Países Bajos',
                'short_name' => 'NED',
                'tla' => 'NED',
                'country' => 'Países Bajos',
                'type' => 'national',
                'crest_url' => 'https://crests.football-data.org/ned.svg'
            ],
            [
                'name' => 'Bélgica',
                'short_name' => 'BEL',
                'tla' => 'BEL',
                'country' => 'Bélgica',
                'type' => 'national',
                'crest_url' => 'https://crests.football-data.org/bel.svg'
            ],
            [
                'name' => 'Uruguay',
                'short_name' => 'URU',
                'tla' => 'URU',
                'country' => 'Uruguay',
                'type' => 'national',
                'crest_url' => 'https://crests.football-data.org/uru.svg'
            ],
            [
                'name' => 'Colombia',
                'short_name' => 'COL',
                'tla' => 'COL',
                'country' => 'Colombia',
                'type' => 'national',
                'crest_url' => 'https://crests.football-data.org/col.svg'
            ],
            [
                'name' => 'México',
                'short_name' => 'MEX',
                'tla' => 'MEX',
                'country' => 'México',
                'type' => 'national',
                'crest_url' => 'https://crests.football-data.org/mex.svg'
            ],
            [
                'name' => 'Chile',
                'short_name' => 'CHI',
                'tla' => 'CHI',
                'country' => 'Chile',
                'type' => 'national',
                'crest_url' => 'https://crests.football-data.org/chi.svg'
            ],
            [
                'name' => 'Perú',
                'short_name' => 'PER',
                'tla' => 'PER',
                'country' => 'Perú',
                'type' => 'national',
                'crest_url' => 'https://crests.football-data.org/per.svg'
            ]
        ];

        foreach ($nationalTeams as $teamData) {
            $team = Team::updateOrCreate(
                ['tla' => $teamData['tla']],
                $teamData
            );

            // Asociar el equipo a la competencia de Amistosos de Selecciones
            if ($competition) {
                $team->competitions()->syncWithoutDetaching([$competition->id]);
            }
        }
    }
}
