<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\FootballMatch;
use Illuminate\Database\Seeder;

class FootballMatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $competition = Competition::where('type', 'laliga')->first();

        if (!$competition) {
            $this->command->error('La Liga competition not found. Please run CompetitionSeeder first.');
            return;
        }

        $matches = [
            [
                'home_team' => 'Real Oviedo',
                'away_team' => 'RCD Espanyol',
                'date' => '2025-10-17 15:00:00',
                'status' => 'SCHEDULED',
                'league' => 'La Liga',
                'competition_id' => $competition->id,
                'external_id' => 'laliga-2025-9-1',
            ],
            [
                'home_team' => 'Sevilla FC',
                'away_team' => 'RCD Mallorca',
                'date' => '2025-10-18 08:00:00',
                'status' => 'SCHEDULED',
                'league' => 'La Liga',
                'competition_id' => $competition->id,
                'external_id' => 'laliga-2025-9-2',
            ],
            [
                'home_team' => 'FC Barcelona',
                'away_team' => 'Girona FC',
                'date' => '2025-10-18 10:15:00',
                'status' => 'SCHEDULED',
                'league' => 'La Liga',
                'competition_id' => $competition->id,
                'external_id' => 'laliga-2025-9-3',
            ],
            [
                'home_team' => 'Villarreal CF',
                'away_team' => 'Real Betis Balompié',
                'date' => '2025-10-18 12:30:00',
                'status' => 'SCHEDULED',
                'league' => 'La Liga',
                'competition_id' => $competition->id,
                'external_id' => 'laliga-2025-9-4',
            ],
            [
                'home_team' => 'Atlético de Madrid',
                'away_team' => 'CA Osasuna',
                'date' => '2025-10-18 15:00:00',
                'status' => 'SCHEDULED',
                'league' => 'La Liga',
                'competition_id' => $competition->id,
                'external_id' => 'laliga-2025-9-5',
            ],
            [
                'home_team' => 'Elche CF',
                'away_team' => 'Athletic Bilbao',
                'date' => '2025-10-19 08:00:00',
                'status' => 'SCHEDULED',
                'league' => 'La Liga',
                'competition_id' => $competition->id,
                'external_id' => 'laliga-2025-9-6',
            ],
            [
                'home_team' => 'Celta de Vigo',
                'away_team' => 'Real Sociedad',
                'date' => '2025-10-19 10:15:00',
                'status' => 'SCHEDULED',
                'league' => 'La Liga',
                'competition_id' => $competition->id,
                'external_id' => 'laliga-2025-9-7',
            ],
            [
                'home_team' => 'Levante UD',
                'away_team' => 'Rayo Vallecano',
                'date' => '2025-10-19 12:30:00',
                'status' => 'SCHEDULED',
                'league' => 'La Liga',
                'competition_id' => $competition->id,
                'external_id' => 'laliga-2025-9-8',
            ],
            [
                'home_team' => 'Getafe CF',
                'away_team' => 'Real Madrid',
                'date' => '2025-10-19 15:00:00',
                'status' => 'SCHEDULED',
                'league' => 'La Liga',
                'competition_id' => $competition->id,
                'external_id' => 'laliga-2025-9-9',
            ],
            [
                'home_team' => 'Deportivo Alavés',
                'away_team' => 'Valencia CF',
                'date' => '2025-10-20 15:00:00',
                'status' => 'SCHEDULED',
                'league' => 'La Liga',
                'competition_id' => $competition->id,
                'external_id' => 'laliga-2025-9-10',
            ],
        ];

        foreach ($matches as $match) {
            FootballMatch::updateOrCreate(
                ['external_id' => $match['external_id']],
                $match
            );
        }

        $this->command->info('FootballMatchSeeder completed: 10 matches inserted for La Liga jornada 9.');
    }
}
