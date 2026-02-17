<?php

namespace Database\Seeders;

use App\Models\TemplateQuestion;
use Illuminate\Database\Seeder;

class CreateNewQuestionTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Crea 3 nuevos tipos de preguntas de plantilla:
     * - S1: Late Goal (Gol en últimos 15 minutos)
     * - S5: Goal Before Halftime (Gol antes del descanso)
     * - S2: Shots on Target (Tiros al arco)
     */
    public function run(): void
    {
        // ✅ S1: LATE GOAL - Gol en los últimos 15 minutos
        TemplateQuestion::firstOrCreate(
            [
                'type' => 'predictive',
                'text' => '¿Habrá gol en los últimos 15 minutos del partido?',
            ],
            [
                'type' => 'predictive',
                'options' => [
                    ['text' => 'Sí, habrá gol', 'is_correct' => false],
                    ['text' => 'No, no habrá gol', 'is_correct' => false],
                ],
                'is_featured' => false,
            ]
        );

        // ✅ S5: GOAL BEFORE HALFTIME - Gol antes del descanso
        TemplateQuestion::firstOrCreate(
            [
                'type' => 'predictive',
                'text' => '¿Habrá al menos un gol en el primer tiempo?',
            ],
            [
                'type' => 'predictive',
                'options' => [
                    ['text' => 'Sí, habrá gol', 'is_correct' => false],
                    ['text' => 'No, no habrá gol', 'is_correct' => false],
                ],
                'is_featured' => false,
            ]
        );

        // ✅ S2: SHOTS ON TARGET - Tiros al arco
        TemplateQuestion::firstOrCreate(
            [
                'type' => 'predictive',
                'text' => '¿Cuál equipo tendrá más tiros al arco?',
            ],
            [
                'type' => 'predictive',
                'options' => [
                    ['text' => '{{ home_team }}', 'is_correct' => false],
                    ['text' => '{{ away_team }}', 'is_correct' => false],
                    ['text' => 'Igual cantidad', 'is_correct' => false],
                ],
                'is_featured' => false,
            ]
        );

        $this->command->info('✅ 3 nuevas plantillas de preguntas creadas exitosamente:');
        $this->command->info('');
        $this->command->info('   S1: ¿Habrá gol en los últimos 15 minutos del partido?');
        $this->command->info('   S5: ¿Habrá al menos un gol en el primer tiempo?');
        $this->command->info('   S2: ¿Cuál equipo tendrá más tiros al arco?');
        $this->command->info('');
        $this->command->info('📝 Nota: Las preguntas se crearán automáticamente en partidos a través del');
        $this->command->info('   comando CreatePredictiveQuestionsJob basándose en estas plantillas.');
    }
}
