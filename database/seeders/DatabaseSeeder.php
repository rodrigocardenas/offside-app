<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);

        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            CompetitionSeeder::class,
            FootballMatchSeeder::class,
            Group83QuestionSeeder::class,
            GroupSeeder::class,
            SocialQuestionsSeeder::class,
            TemplateQuestionSeeder::class,
            NationalTeamsSeeder::class,
        ]);
    }
}
