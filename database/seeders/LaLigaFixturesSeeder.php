<?php

namespace Database\Seeders;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Models\Competition;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class LaLigaFixturesSeeder extends Seeder
{
    public function run(): void
    {
        $competition = Competition::firstOrCreate(
            ['name' => 'La Liga'],
            ['name' => 'La Liga', 'code' => 'LALIGA']
        );

        $fixtures = [
            ['Real Madrid', 'Atlético Madrid', 'Jornada 1', 'Santiago Bernabéu'],
            ['Barcelona', 'Valencia', 'Jornada 1', 'Spotify Camp Nou'],
            ['Sevilla', 'Real Betis', 'Jornada 1', 'Ramón Sánchez Pizjuán'],
            ['Athletic Club', 'Villarreal', 'Jornada 2', 'San Mamés'],
            ['Real Sociedad', 'Getafe', 'Jornada 2', 'Reale Arena'],
            ['Osasuna', 'Rayo Vallecano', 'Jornada 3', 'El Sadar'],
            ['Girona', 'Mallorca', 'Jornada 3', 'Estadi Cornellà-El Prat'],
            ['Celta Vigo', 'Las Palmas', 'Jornada 4', 'Balaídos'],
            ['Real Valladolid', 'Leganés', 'Jornada 4', 'José Zorrilla'],
            ['Almería', 'Cádiz', 'Jornada 5', 'Power Horse'],
        ];

        $date = Carbon::now()->addDays(1);

        foreach ($fixtures as $idx => [$home, $away, $stage, $stadium]) {
            // Obtener o crear equipos
            $homeTeam = Team::firstOrCreate(
                ['name' => $home],
                ['name' => $home, 'external_id' => 'gemini_' . strtolower(str_replace(' ', '_', $home))]
            );
            $awayTeam = Team::firstOrCreate(
                ['name' => $away],
                ['name' => $away, 'external_id' => 'gemini_' . strtolower(str_replace(' ', '_', $away))]
            );

            // Crear fixture
            FootballMatch::firstOrCreate(
                [
                    'home_team' => $home,
                    'away_team' => $away,
                    'date' => $date->copy()->addHours($idx),
                ],
                [
                    'home_team' => $home,
                    'away_team' => $away,
                    'date' => $date->copy()->addHours($idx),
                    'status' => 'scheduled',
                    'competition_id' => $competition->id,
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                    'league' => 'La Liga',
                    'stadium' => $stadium,
                ]
            );
        }

        $this->command->info("✅ 10 fixtures de La Liga creados exitosamente");
    }
}

