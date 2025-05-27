<?php

namespace Database\Seeders;

use App\Models\Competition;
use Illuminate\Database\Seeder;

class CompetitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $competitions = [
            [
                'name' => 'La Liga',
                'type' => 'laliga',
                'description' => 'Liga española de fútbol'
            ],
            [
                'name' => 'Premier League',
                'type' => 'premier',
                'description' => 'Liga inglesa de fútbol'
            ],
            [
                'name' => 'Champions League',
                'type' => 'champions',
                'description' => 'Liga de campeones de Europa'
            ]
        ];

        foreach ($competitions as $competition) {
            Competition::create($competition);
        }
    }
}
