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
                'text' => '¿Cuál es el equipo que más veces ha ganado la Champions League?',
                'options' => [
                    ['text' => 'Real Madrid', 'is_correct' => true],
                    ['text' => 'AC Milan', 'is_correct' => false],
                    ['text' => 'Bayern Munich', 'is_correct' => false],
                    ['text' => 'Liverpool', 'is_correct' => false],
                ]
            ],
            // Q2
            [
                'type' => 'quiz',
                'text' => '¿En qué año se disputó el primer Mundial de Fútbol?',
                'options' => [
                    ['text' => '1926', 'is_correct' => false],
                    ['text' => '1930', 'is_correct' => true],
                    ['text' => '1934', 'is_correct' => false],
                    ['text' => '1938', 'is_correct' => false],
                ]
            ],
            // Q3
            [
                'type' => 'quiz',
                'text' => '¿Cuál fue el máximo goleador en la historia de los Mundiales de Fútbol?',
                'options' => [
                    ['text' => 'Pelé', 'is_correct' => false],
                    ['text' => 'Cristiano Ronaldo', 'is_correct' => false],
                    ['text' => 'Miroslav Klose', 'is_correct' => true],
                    ['text' => 'Gerd Müller', 'is_correct' => false],
                ]
            ],
            // Q4
            [
                'type' => 'quiz',
                'text' => '¿Cuántos títulos de Copa del Mundo tiene Brasil?',
                'options' => [
                    ['text' => '4', 'is_correct' => false],
                    ['text' => '5', 'is_correct' => true],
                    ['text' => '6', 'is_correct' => false],
                    ['text' => '7', 'is_correct' => false],
                ]
            ],
            // Q5
            [
                'type' => 'quiz',
                'text' => '¿En qué país se disputó la final de la Copa del Mundo de 2010?',
                'options' => [
                    ['text' => 'España', 'is_correct' => false],
                    ['text' => 'Sudáfrica', 'is_correct' => true],
                    ['text' => 'Brasil', 'is_correct' => false],
                    ['text' => 'Alemania', 'is_correct' => false],
                ]
            ],
            // Q6
            [
                'type' => 'quiz',
                'text' => '¿En qué país se juega la Serie A?',
                'options' => [
                    ['text' => 'España', 'is_correct' => false],
                    ['text' => 'Italia', 'is_correct' => true],
                    ['text' => 'Portugal', 'is_correct' => false],
                    ['text' => 'Francia', 'is_correct' => false],
                ]
            ],
            // Q7
            [
                'type' => 'quiz',
                'text' => '¿Quién es el máximo goleador histórico de la selección de España?',
                'options' => [
                    ['text' => 'Raúl', 'is_correct' => false],
                    ['text' => 'Fernando Torres', 'is_correct' => false],
                    ['text' => 'David Villa', 'is_correct' => true],
                    ['text' => 'Sergio Ramos', 'is_correct' => false],
                ]
            ],
            // Q8
            [
                'type' => 'quiz',
                'text' => '¿Cuál es la competición de fútbol más antigua del mundo?',
                'options' => [
                    ['text' => 'Copa del Mundo', 'is_correct' => false],
                    ['text' => 'FA Cup', 'is_correct' => true],
                    ['text' => 'Copa Libertadores', 'is_correct' => false],
                    ['text' => 'Liga de Campeones', 'is_correct' => false],
                ]
            ],
            // Q9
            [
                'type' => 'quiz',
                'text' => '¿Qué selección tiene más Mundiales de fútbol femenino?',
                'options' => [
                    ['text' => 'EEUU', 'is_correct' => true],
                    ['text' => 'Brasil', 'is_correct' => false],
                    ['text' => 'Noruega', 'is_correct' => false],
                    ['text' => 'Alemania', 'is_correct' => false],
                ]
            ],
            // Q10
            [
                'type' => 'quiz',
                'text' => '¿Quién es el jugador más joven en disputar un mundial?',
                'options' => [
                    ['text' => 'Luke Brooke-Smith', 'is_correct' => true],
                    ['text' => 'Norman Whiteside', 'is_correct' => false],
                    ['text' => 'Pelé', 'is_correct' => false],
                    ['text' => 'Hugo Sánchez', 'is_correct' => false],
                ]
            ],
        ];

        foreach ($quizQuestions as $questionData) {
            TemplateQuestion::create($questionData);
        }

        $this->command->info("✅ 10 preguntas de quiz creadas exitosamente");
    }
}
