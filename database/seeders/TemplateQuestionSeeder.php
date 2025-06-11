<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TemplateQuestion;

class TemplateQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Preguntas predictivas
        TemplateQuestion::create([
            'type' => 'predictive',
            'text' => '¿Cuál será el resultado del partido?',
            'options' => [
                ['text' => 'Victoria {{home_team}}', 'is_correct' => false],
                ['text' => 'Victoria {{away_team}}', 'is_correct' => false],
                ['text' => 'Empate', 'is_correct' => false]
            ]
        ]);

        TemplateQuestion::create([
            'type' => 'predictive',
            'text' => '¿Cuál equipo tendrá más posesión en el partido?',
            'options' => [
                ['text' => '{{home_team}}', 'is_correct' => false],
                ['text' => '{{away_team}}', 'is_correct' => false]
            ]
        ]);

        TemplateQuestion::create([
            'type' => 'predictive',
            'text' => '¿Cuál equipo anotará el primer gol en el partido?',
            'options' => [
                ['text' => '{{home_team}}', 'is_correct' => false],
                ['text' => '{{away_team}}', 'is_correct' => false],
                ['text' => 'Ninguno', 'is_correct' => false]
            ]
        ]);

        TemplateQuestion::create([
            'type' => 'predictive',
            'text' => '¿Cuál equipo recibirá más faltas en el partido?',
            'options' => [
                ['text' => '{{home_team}}', 'is_correct' => false],
                ['text' => '{{away_team}}', 'is_correct' => false],
                ['text' => 'Ninguno', 'is_correct' => false]
            ]
        ]);

        TemplateQuestion::create([
            'type' => 'predictive',
            'text' => '¿Cuál equipo recibirá más tarjetas amarillas en el partido?',
            'options' => [
                ['text' => '{{home_team}}', 'is_correct' => false],
                ['text' => '{{away_team}}', 'is_correct' => false],
                ['text' => 'Ninguno', 'is_correct' => false]
            ]
        ]);

        TemplateQuestion::create([
            'type' => 'predictive',
            'text' => '¿Cuál equipo recibirá más tarjetas rojas en el partido?',
            'options' => [
                ['text' => '{{home_team}}', 'is_correct' => false],
                ['text' => '{{away_team}}', 'is_correct' => false],
                ['text' => 'Ninguno', 'is_correct' => false]
            ]
        ]);

        TemplateQuestion::create([
            'type' => 'predictive',
            'text' => '¿Cuál equipo anotará un autogol?',
            'options' => [
                ['text' => '{{home_team}}', 'is_correct' => false],
                ['text' => '{{away_team}}', 'is_correct' => false],
                ['text' => 'Ninguno', 'is_correct' => false]
            ]
        ]);

        TemplateQuestion::create([
            'type' => 'predictive',
            'text' => '¿Cuál equipo anotará un gol de penal?',
            'options' => [
                ['text' => '{{home_team}}', 'is_correct' => false],
                ['text' => '{{away_team}}', 'is_correct' => false],
                ['text' => 'Ninguno', 'is_correct' => false]
            ]
        ]);

        TemplateQuestion::create([
            'type' => 'predictive',
            'text' => '¿Cuál equipo anotará un gol de tiro libre?',
            'options' => [
                ['text' => '{{home_team}}', 'is_correct' => false],
                ['text' => '{{away_team}}', 'is_correct' => false],
                ['text' => 'Ninguno', 'is_correct' => false]
            ]
        ]);

        TemplateQuestion::create([
            'type' => 'predictive',
            'text' => '¿Cuál equipo anotará un gol de tiro de esquina?',
            'options' => [
                ['text' => '{{home_team}}', 'is_correct' => false],
                ['text' => '{{away_team}}', 'is_correct' => false],
                ['text' => 'Ninguno', 'is_correct' => false]
            ]
        ]);

        TemplateQuestion::create([
            'type' => 'predictive',
            'text' => '¿Cuál equipo anotará el último gol del partido?',
            'options' => [
                ['text' => '{{home_team}}', 'is_correct' => false],
                ['text' => '{{away_team}}', 'is_correct' => false],
                ['text' => 'Ninguno', 'is_correct' => false]
            ]
        ]);
    }
}
