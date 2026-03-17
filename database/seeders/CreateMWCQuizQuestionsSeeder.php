<?php

namespace Database\Seeders;

use App\Models\TemplateQuestion;
use Illuminate\Database\Seeder;

class CreateMWCQuizQuestionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Crea 10 preguntas de tipo 'quiz' para el Mobile World Congress.
     * Estas son preguntas de conocimiento general sobre fútbol.
     * Cada pregunta tiene respuestas de múltiple opción.
     *
     * El valor correcto se marca con 'is_correct' => true
     */
    public function run(): void
    {
        $quizQuestions = [
            // Q1
            [
                'type' => 'quiz',
                'text' => '¿Cuál fue el primer cambio del partido entre Tottenham vs Atlético de Madrid?',
                'options' => [
                    ['text' => 'Hugo Lloris', 'is_correct' => false],
                    ['text' => 'Jan Oblak', 'is_correct' => false],
                    ['text' => 'Antonin Kinsky', 'is_correct' => true],
                    ['text' => 'Mathys Tel', 'is_correct' => false],
                ]
            ],
            // Q2
            [
                'type' => 'quiz',
                'text' => '¿Cuál de estos jugadores ha vestido la camiseta del PSG y también la del Chelsea?',
                'options' => [
                    ['text' => 'Eden Hazard', 'is_correct' => false],
                    ['text' => 'Kylian Mbappé', 'is_correct' => false],
                    ['text' => 'Thiago Silva', 'is_correct' => true],
                    ['text' => 'Didier Drogba', 'is_correct' => false],
                ]
            ],
            // Q3
            [
                'type' => 'quiz',
                'text' => '¿Cuál sería el rival del Bodo en caso de pasar a 4tos?',
                'options' => [
                    ['text' => 'Arsenal/Sporting Lisboa', 'is_correct' => true],
                    ['text' => 'Barcelona/Westham', 'is_correct' => false],
                    ['text' => 'Arsenal/Benfica', 'is_correct' => false],
                    ['text' => 'Barcelona/Newcastle', 'is_correct' => false],
                ]
            ],
            // Q4
            [
                'type' => 'quiz',
                'text' => '¿Cómo apodan popularmente al Leverkusen debido a sus orígenes?',
                'options' => [
                    ['text' => 'Los Mineros', 'is_correct' => false],
                    ['text' => 'Los Toros', 'is_correct' => false],
                    ['text' => 'El equipo de las aspirinas', 'is_correct' => true],
                    ['text' => 'Los Gunners', 'is_correct' => false],
                ]
            ],
            // Q5
            [
                'type' => 'quiz',
                'text' => '¿Cuál de estos entrenadores ha ganado la Champions como jugador y como director técnico?',
                'options' => [
                    ['text' => 'Carlo Ancelotti', 'is_correct' => true],
                    ['text' => 'Luis Enrique', 'is_correct' => false],
                    ['text' => 'Mikel Arteta', 'is_correct' => false],
                    ['text' => 'Xabi Alonso', 'is_correct' => false],
                ]
            ],
            // Q6
            [
                'type' => 'quiz',
                'text' => '¿Cuál es la instancia más lejana que ha alcanzado el Sporting de Lisboa en la historia de la Champions League?',
                'options' => [
                    ['text' => 'Final', 'is_correct' => false],
                    ['text' => 'Semifinales', 'is_correct' => false],
                    ['text' => 'Cuartos de final', 'is_correct' => true],
                    ['text' => 'Octavos de final', 'is_correct' => false],
                ]
            ],
            // Q7
            [
                'type' => 'quiz',
                'text' => '¿Qué resultado le sirve al Chelsea para pasar a 4tos?',
                'options' => [
                    ['text' => '6-3', 'is_correct' => false],
                    ['text' => '4-1', 'is_correct' => false],
                    ['text' => '2-0', 'is_correct' => false],
                    ['text' => '5-1', 'is_correct' => true],
                ]
            ],

        ];

        foreach ($quizQuestions as $questionData) {
            TemplateQuestion::create($questionData);
        }

        $this->command->info("✅ 10 preguntas de quiz creadas exitosamente");
    }
}
