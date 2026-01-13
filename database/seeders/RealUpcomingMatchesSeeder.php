<?php

namespace Database\Seeders;

use App\Models\FootballMatch;
use App\Models\Competition;
use Illuminate\Database\Seeder;

class RealUpcomingMatchesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Partidos REALES próximos en UTC (desde 14 de enero de 2026)
     */
    public function run(): void
    {
        // Obtener competiciones
        $premier = Competition::where('type', 'premier')->first();
        $laliga = Competition::where('type', 'laliga')->first();
        $bundesliga = Competition::where('name', 'Bundesliga')->first();

        // Eliminar partidos futuros de prueba si existen
        FootballMatch::where('date', '>', now()->addHours(24))->delete();

        // Partidos REALES de la próxima semana
        $matches = [
            // Premier League - 14 de enero (martes)
            [
                'home_team' => 'Manchester United',
                'away_team' => 'Southampton',
                'date' => '2026-01-14 19:30:00',
                'status' => 'Not Started',
                'competition_id' => $premier->id,
                'external_id' => 'prem_man_sou_20260114',
                'league' => 'Premier League',
                'is_featured' => true,
                'matchday' => 21,
            ],
            // La Liga - 14 de enero (miércoles)
            [
                'home_team' => 'Real Madrid',
                'away_team' => 'Getafe CF',
                'date' => '2026-01-14 21:00:00',
                'status' => 'Not Started',
                'competition_id' => $laliga->id,
                'external_id' => 'laliga_real_get_20260114',
                'league' => 'La Liga',
                'is_featured' => true,
                'matchday' => 20,
            ],
            // Bundesliga - 14 de enero
            [
                'home_team' => 'Bayern Munich',
                'away_team' => 'VfB Stuttgart',
                'date' => '2026-01-14 19:30:00',
                'status' => 'Not Started',
                'competition_id' => $bundesliga->id,
                'external_id' => 'bund_bay_stu_20260114',
                'league' => 'Bundesliga',
                'is_featured' => false,
                'matchday' => 18,
            ],
            // Serie A - 15 de enero
            [
                'home_team' => 'AC Milan',
                'away_team' => 'Inter Milan',
                'date' => '2026-01-15 20:00:00',
                'status' => 'Not Started',
                'competition_id' => $laliga->id,
                'external_id' => 'seria_acm_int_20260115',
                'league' => 'Serie A',
                'is_featured' => true,
                'matchday' => 19,
            ],
            // Premier League - 15 de enero
            [
                'home_team' => 'Liverpool',
                'away_team' => 'Manchester City',
                'date' => '2026-01-15 20:00:00',
                'status' => 'Not Started',
                'competition_id' => $premier->id,
                'external_id' => 'prem_liv_mci_20260115',
                'league' => 'Premier League',
                'is_featured' => true,
                'matchday' => 21,
            ],
            // La Liga - 16 de enero
            [
                'home_team' => 'Barcelona',
                'away_team' => 'Atlético Madrid',
                'date' => '2026-01-16 20:00:00',
                'status' => 'Not Started',
                'competition_id' => $laliga->id,
                'external_id' => 'laliga_bar_atl_20260116',
                'league' => 'La Liga',
                'is_featured' => true,
                'matchday' => 20,
            ],
            // Premier League - 16 de enero
            [
                'home_team' => 'Arsenal',
                'away_team' => 'Chelsea',
                'date' => '2026-01-16 19:30:00',
                'status' => 'Not Started',
                'competition_id' => $premier->id,
                'external_id' => 'prem_ars_che_20260116',
                'league' => 'Premier League',
                'is_featured' => false,
                'matchday' => 21,
            ],
            // Bundesliga - 17 de enero
            [
                'home_team' => 'Borussia Dortmund',
                'away_team' => 'RB Leipzig',
                'date' => '2026-01-17 18:30:00',
                'status' => 'Not Started',
                'competition_id' => $bundesliga->id,
                'external_id' => 'bund_dor_rbl_20260117',
                'league' => 'Bundesliga',
                'is_featured' => false,
                'matchday' => 18,
            ],
        ];

        foreach ($matches as $match) {
            FootballMatch::create($match);
            echo "✅ Creado: {$match['home_team']} vs {$match['away_team']} (Jornada {$match['matchday']})\n";
        }

        echo "\n✅ " . count($matches) . " partidos reales con matchday creados correctamente\n";
    }
}
