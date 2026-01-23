<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\FootballMatch;
use App\Models\Group;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\TemplateQuestion;
use Illuminate\Database\Seeder;

class Group83QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $competition = Competition::where('type', 'laliga')->first();

        if (!$competition) {
            $this->command->error('La Liga competition not found.');
            return;
        }

        $group = Group::find(83);

        if (!$group) {
            $this->command->error('Group with id 83 not found. Please ensure GroupSeeder has been run.');
            return;
        }

        $groupId = 83;

        $funQuestions = [
            [
                'external_id' => 'laliga-2025-9-1',
                'text' => '¿Marcará un gol olímpico en el partido Real Oviedo vs RCD Espanyol?',
                'options' => ['Sí', 'No'],
                'correct' => 'No', // Assuming for fun
            ],
            [
                'external_id' => 'laliga-2025-9-2',
                'text' => '¿Hará un hat-trick el delantero de Sevilla FC contra RCD Mallorca?',
                'options' => ['Sí', 'No'],
                'correct' => 'No',
            ],
            [
                'external_id' => 'laliga-2025-9-3',
                'text' => '¿Hará Lamine Yamal un caño en el partido FC Barcelona vs Girona FC?',
                'options' => ['Sí', 'No'],
                'correct' => 'Sí',
            ],
            [
                'external_id' => 'laliga-2025-9-4',
                'text' => '¿Salvará el portero un penalti en Villarreal CF vs Real Betis Balompié?',
                'options' => ['Sí', 'No'],
                'correct' => 'No',
            ],
            [
                'external_id' => 'laliga-2025-9-5',
                'text' => '¿Habrá un autogol en Atlético de Madrid vs CA Osasuna?',
                'options' => ['Sí', 'No'],
                'correct' => 'No',
            ],
            [
                'external_id' => 'laliga-2025-9-6',
                'text' => '¿Marcará un gol de chilena en Elche CF vs Athletic Bilbao?',
                'options' => ['Sí', 'No'],
                'correct' => 'No',
            ],
            [
                'external_id' => 'laliga-2025-9-7',
                'text' => '¿Habrá más de 3 tarjetas rojas en Celta de Vigo vs Real Sociedad?',
                'options' => ['Sí', 'No'],
                'correct' => 'No',
            ],
            [
                'external_id' => 'laliga-2025-9-8',
                'text' => '¿Ganará el equipo local en Levante UD vs Rayo Vallecano?',
                'options' => ['Sí', 'No'],
                'correct' => 'Sí',
            ],
            [
                'external_id' => 'laliga-2025-9-9',
                'text' => '¿Será un partido con más de 4 goles en Getafe CF vs Real Madrid?',
                'options' => ['Sí', 'No'],
                'correct' => 'No',
            ],
            [
                'external_id' => 'laliga-2025-9-10',
                'text' => '¿Habrá un empate en Deportivo Alavés vs Valencia CF?',
                'options' => ['Sí', 'No'],
                'correct' => 'Sí',
            ],
        ];

        foreach ($funQuestions as $qData) {
            $match = FootballMatch::where('external_id', $qData['external_id'])->first();

            if (!$match) {
                $this->command->error("Match with external_id {$qData['external_id']} not found.");
                continue;
            }

            // Create or update TemplateQuestion
            $template = TemplateQuestion::updateOrCreate(
                ['text' => $qData['text'], 'match_id' => $match->id],
                [
                    'type' => 'multiple_choice',
                    'options' => $qData['options'],
                    'competition_id' => $competition->id,
                    'match_date' => $match->date,
                ]
            );

            // Create or update Question
            $question = Question::updateOrCreate(
                ['title' => $qData['text'], 'group_id' => $groupId, 'match_id' => $match->id],
                [
                    'type' => 'multiple_choice',
                    'points' => 10,
                    'available_until' => $match->date,
                    'template_question_id' => $template->id,
                    'competition_id' => $competition->id,
                    'category' => 'fun',
                ]
            );

            // Create QuestionOptions
            foreach ($qData['options'] as $optionText) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'text' => $optionText,
                    'is_correct' => $optionText === $qData['correct'],
                ]);
            }
        }

        $this->command->info('Group83QuestionSeeder completed: 10 fun questions created for group 83.');
    }
}
