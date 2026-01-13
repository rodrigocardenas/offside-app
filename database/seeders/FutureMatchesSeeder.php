<?php

namespace Database\Seeders;

use App\Models\FootballMatch;
use App\Models\Competition;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class FutureMatchesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener competiciones
        $premier = Competition::where('type', 'premier')->first();
        $laliga = Competition::where('type', 'laliga')->first();
        $champions = Competition::where('type', 'champions')->first();

        if (!$premier && !$laliga && !$champions) {
            $this->command->error('❌ No competitions found');
            return;
        }

        // Crear partidos para los próximos 10 días
        $matches = [];

        // Premier League
        if ($premier) {
            $matches[] = [
                'home_team' => 'Manchester United',
                'away_team' => 'Liverpool',
                'date' => now()->addDay()->setTime(15, 0),
                'competition_id' => $premier->id,
                'is_featured' => true,
                'league' => 'premier',
            ];
            $matches[] = [
                'home_team' => 'Arsenal',
                'away_team' => 'Manchester City',
                'date' => now()->addDays(2)->setTime(15, 30),
                'competition_id' => $premier->id,
                'is_featured' => false,
                'league' => 'premier',
            ];
            $matches[] = [
                'home_team' => 'Chelsea',
                'away_team' => 'Tottenham',
                'date' => now()->addDays(3)->setTime(20, 0),
                'competition_id' => $premier->id,
                'is_featured' => false,
                'league' => 'premier',
            ];
        }

        // La Liga
        if ($laliga) {
            $matches[] = [
                'home_team' => 'Real Madrid',
                'away_team' => 'Barcelona',
                'date' => now()->addDays(2)->setTime(21, 0),
                'competition_id' => $laliga->id,
                'is_featured' => true,
                'league' => 'laliga',
            ];
            $matches[] = [
                'home_team' => 'Atlético Madrid',
                'away_team' => 'Sevilla',
                'date' => now()->addDays(4)->setTime(19, 0),
                'competition_id' => $laliga->id,
                'is_featured' => false,
                'league' => 'laliga',
            ];
        }

        // Champions League
        if ($champions) {
            $matches[] = [
                'home_team' => 'Bayern Munich',
                'away_team' => 'Paris Saint-Germain',
                'date' => now()->addDays(5)->setTime(20, 0),
                'competition_id' => $champions->id,
                'is_featured' => false,
                'league' => 'champions',
            ];
        }

        // Insertar partidos
        $created = 0;
        foreach ($matches as $match) {
            $existing = FootballMatch::where('home_team', $match['home_team'])
                ->where('away_team', $match['away_team'])
                ->where('date', '>=', now())
                ->exists();

            if (!$existing) {
                FootballMatch::create($match + [
                    'status' => 'Not Started',
                ]);
                $created++;
            }
        }

        $this->command->info("✅ Created {$created} future matches");

        // Show summary
        $futureCount = FootballMatch::where('date', '>=', now())->count();
        $this->command->info("📊 Total future matches now: {$futureCount}");
    }
}
