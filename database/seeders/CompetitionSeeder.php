<?php

namespace Database\Seeders;

use App\Models\Competition;
use Illuminate\Database\Seeder;

class CompetitionSeeder extends Seeder
{
    public function run(): void
    {
        $competitions = [
            [
                'name' => 'UEFA Champions League',
                'type' => 'champions-league',
                'country' => 'Europa'
            ],
            [
                'name' => 'La Liga',
                'type' => 'la-liga',
                'country' => 'España'
            ],
            [
                'name' => 'Premier League',
                'type' => 'premier-league',
                'country' => 'Inglaterra'
            ]
        ];

        foreach ($competitions as $competition) {
            Competition::updateOrCreate(
                ['name' => $competition['name']],
                $competition
            );
        }
    }
}
