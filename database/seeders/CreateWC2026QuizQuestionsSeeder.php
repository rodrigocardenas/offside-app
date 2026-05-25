<?php

namespace Database\Seeders;

use App\Models\TemplateQuestion;
use Illuminate\Database\Seeder;

class CreateWC2026QuizQuestionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Crea 10 preguntas de tipo 'quiz' para el FIFA World Cup 2026.
     * Dificultad progresiva: 4 fáciles, 4 intermedias, 2 difíciles.
     * Puntos: fácil=100, intermedio=200, difícil=300
     */
    public function run(): void
    {
        $quizQuestions = [

            // ─── FÁCIL (Q1-Q4) ────────────────────────────────────────────────
            [
                'type' => 'quiz',
                'text' => '¿En qué países se celebrará la Copa del Mundo 2026?',
                'options' => [
                    ['text' => 'México, Brasil y Argentina',         'is_correct' => false],
                    ['text' => 'Estados Unidos, México y Canadá',    'is_correct' => true],
                    ['text' => 'Canadá, Argentina y Chile',          'is_correct' => false],
                    ['text' => 'EE.UU., Colombia y México',          'is_correct' => false],
                ],
            ],
            [
                'type' => 'quiz',
                'text' => '¿Cuántos equipos participarán en el Mundial 2026, una cifra récord?',
                'options' => [
                    ['text' => '32 equipos', 'is_correct' => false],
                    ['text' => '40 equipos', 'is_correct' => false],
                    ['text' => '48 equipos', 'is_correct' => true],
                    ['text' => '64 equipos', 'is_correct' => false],
                ],
            ],
            [
                'type' => 'quiz',
                'text' => '¿Cuál selección ganó el Mundial de Qatar 2022?',
                'options' => [
                    ['text' => 'Francia',   'is_correct' => false],
                    ['text' => 'Brasil',    'is_correct' => false],
                    ['text' => 'Argentina', 'is_correct' => true],
                    ['text' => 'Croacia',   'is_correct' => false],
                ],
            ],
            [
                'type' => 'quiz',
                'text' => '¿Quién es el máximo goleador en la historia de los Mundiales de Fútbol?',
                'options' => [
                    ['text' => 'Ronaldo (Brasil)',           'is_correct' => false],
                    ['text' => 'Miroslav Klose — 16 goles', 'is_correct' => true],
                    ['text' => 'Pelé',                      'is_correct' => false],
                    ['text' => 'Gerd Müller',                'is_correct' => false],
                ],
            ],

            // ─── INTERMEDIO (Q5-Q8) ───────────────────────────────────────────
            [
                'type' => 'quiz',
                'text' => '¿Cuántas veces ha ganado Brasil la Copa del Mundo?',
                'options' => [
                    ['text' => '3 veces', 'is_correct' => false],
                    ['text' => '4 veces', 'is_correct' => false],
                    ['text' => '5 veces', 'is_correct' => true],
                    ['text' => '6 veces', 'is_correct' => false],
                ],
            ],
            [
                'type' => 'quiz',
                'text' => '¿En qué estadio se jugará la final del Mundial 2026?',
                'options' => [
                    ['text' => 'SoFi Stadium — Los Ángeles',                    'is_correct' => false],
                    ['text' => 'Estadio Azteca — Ciudad de México',              'is_correct' => false],
                    ['text' => 'MetLife Stadium — Nueva York / Nueva Jersey',    'is_correct' => true],
                    ['text' => 'Rogers Centre — Toronto',                        'is_correct' => false],
                ],
            ],
            [
                'type' => 'quiz',
                'text' => '¿Cuántos goles marcó Kylian Mbappé en el Mundial de Qatar 2022?',
                'options' => [
                    ['text' => '5 goles', 'is_correct' => false],
                    ['text' => '6 goles', 'is_correct' => false],
                    ['text' => '7 goles', 'is_correct' => false],
                    ['text' => '8 goles', 'is_correct' => true],
                ],
            ],
            [
                'type' => 'quiz',
                'text' => '¿Cuál fue la primera selección africana en alcanzar una semifinal del Mundial?',
                'options' => [
                    ['text' => 'Nigeria',              'is_correct' => false],
                    ['text' => 'Senegal',              'is_correct' => false],
                    ['text' => 'Marruecos (Qatar 2022)', 'is_correct' => true],
                    ['text' => 'Camerún',              'is_correct' => false],
                ],
            ],

            // ─── DIFÍCIL (Q9-Q10) ─────────────────────────────────────────────
            [
                'type' => 'quiz',
                'text' => '¿En qué año se disputó el primer Mundial de Fútbol y qué país fue campeón?',
                'options' => [
                    ['text' => '1934 — Italia',     'is_correct' => false],
                    ['text' => '1930 — Uruguay',    'is_correct' => true],
                    ['text' => '1928 — Argentina',  'is_correct' => false],
                    ['text' => '1930 — Brasil',     'is_correct' => false],
                ],
            ],
            [
                'type' => 'quiz',
                'text' => '¿Qué jugador marcó un hat-trick en la final del Mundial 2022 pero terminó en el equipo perdedor?',
                'options' => [
                    ['text' => 'Antoine Griezmann', 'is_correct' => false],
                    ['text' => 'Olivier Giroud',    'is_correct' => false],
                    ['text' => 'Kylian Mbappé',     'is_correct' => true],
                    ['text' => 'Ousmane Dembélé',   'is_correct' => false],
                ],
            ],
        ];

        foreach ($quizQuestions as $questionData) {
            TemplateQuestion::create($questionData);
        }

        $this->command->info('✅ 10 preguntas quiz del Mundial 2026 creadas (4 fácil, 4 intermedio, 2 difícil)');
    }
}
