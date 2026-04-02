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

        // Generate matches starting from tomorrow
        $startDate = now()->addDay();

        $matches = [
            [
                'home_team' => 'Real Oviedo',
                'away_team' => 'RCD Espanyol',
                'date' => $startDate->copy()->format('Y-m-d H:i:s'),
                'status' => 'SCHEDULED',
                'league' => 'La Liga',
                'competition_id' => $competition->id,
                'external_id' => 'laliga-2026-9-1',
            ],
            [
                'home_team' => 'Sevilla FC',
                'away_team' => 'RCD Mallorca',
                'date' => $startDate->copy()->addDay()->format('Y-m-d 08:00:00'),
                'status' => 'SCHEDULED',
                'league' => 'La Liga',
                'competition_id' => $competition->id,
                'external_id' => 'laliga-2026-9-2',
            ],
            [
                'home_team' => 'FC Barcelona',
                'away_team' => 'Girona FC',
                'date' => $startDate->copy()->addDay()->format('Y-m-d 10:15:00'),
                'status' => 'SCHEDULED',
                'league' => 'La Liga',
                'competition_id' => $competition->id,
                'external_id' => 'laliga-2026-9-3',
            ],
            [
                'home_team' => 'Villarreal CF',
                'away_team' => 'Real Betis Balompié',
                'date' => $startDate->copy()->addDay()->format('Y-m-d 12:30:00'),
                'status' => 'SCHEDULED',
                'league' => 'La Liga',
                'competition_id' => $competition->id,
                'external_id' => 'laliga-2026-9-4',
            ],
            [
                'home_team' => 'Atlético de Madrid',
                'away_team' => 'CA Osasuna',
                'date' => $startDate->copy()->addDay()->format('Y-m-d 15:00:00'),
                'status' => 'SCHEDULED',
                'league' => 'La Liga',
                'competition_id' => $competition->id,
                'external_id' => 'laliga-2026-9-5',
            ],
            [
                'home_team' => 'Elche CF',
                'away_team' => 'Athletic Bilbao',
                'date' => $startDate->copy()->addDays(2)->format('Y-m-d 08:00:00'),
                'status' => 'SCHEDULED',
                'league' => 'La Liga',
                'competition_id' => $competition->id,
                'external_id' => 'laliga-2026-9-6',
            ],
            [
                'home_team' => 'Celta de Vigo',
                'away_team' => 'Real Sociedad',
                'date' => $startDate->copy()->addDays(2)->format('Y-m-d 10:15:00'),
                'status' => 'SCHEDULED',
                'league' => 'La Liga',
                'competition_id' => $competition->id,
                'external_id' => 'laliga-2026-9-7',
            ],
            [
                'home_team' => 'Levante UD',
                'away_team' => 'Rayo Vallecano',
                'date' => $startDate->copy()->addDays(2)->format('Y-m-d 12:30:00'),
                'status' => 'SCHEDULED',
                'league' => 'La Liga',
                'competition_id' => $competition->id,
                'external_id' => 'laliga-2026-9-8',
            ],
            [
                'home_team' => 'Getafe CF',
                'away_team' => 'Real Madrid',
                'date' => $startDate->copy()->addDays(2)->format('Y-m-d 15:00:00'),
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
