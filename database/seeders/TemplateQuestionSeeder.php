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
            'text' => '¿Cuál será el resultado del partido entre {{home_team}} y {{away_team}}?',
            'options' => [
                ['text' => 'Victoria local', 'is_correct' => false],
                ['text' => 'Victoria visitante', 'is_correct' => false],
                ['text' => 'Empate', 'is_correct' => false]
            ]
        ]);

        TemplateQuestion::create([
            'type' => 'predictive',
            'text' => '¿Cuál equipo tendrá más posesión en el partido entre {{home_team}} y {{away_team}}?',
            'options' => [
                ['text' => 'Equipo local', 'is_correct' => false],
                ['text' => 'Equipo visitante', 'is_correct' => false]
            ]
        ]);

        TemplateQuestion::create([
            'type' => 'predictive',
            'text' => '¿Cuál equipo anotará el primer gol en el partido entre {{home_team}} y {{away_team}}?',
            'options' => [
                ['text' => 'Equipo local', 'is_correct' => false],
                ['text' => 'Equipo visitante', 'is_correct' => false]
            ]
        ]);

        TemplateQuestion::create([
            'type' => 'predictive',
            'text' => '¿Cuál equipo recibirá más faltas en el partido entre {{home_team}} y {{away_team}}?',
            'options' => [
                ['text' => 'Equipo local', 'is_correct' => false],
                ['text' => 'Equipo visitante', 'is_correct' => false]
            ]
        ]);

        // Preguntas sobre jugadores
        TemplateQuestion::create([
            'type' => 'predictive',
            'text' => '¿Cuál jugador anotará el primer gol en el partido entre {{home_team}} y {{away_team}}?',
            'options' => [
                ['text' => 'Jugador local 1', 'is_correct' => false],
                ['text' => 'Jugador local 2', 'is_correct' => false],
                ['text' => 'Jugador visitante 1', 'is_correct' => false],
                ['text' => 'Jugador visitante 2', 'is_correct' => false]
            ]
        ]);

        TemplateQuestion::create([
            'type' => 'predictive',
            'text' => '¿Cuál jugador recibirá más faltas en el partido entre {{home_team}} y {{away_team}}?',
            'options' => [
                ['text' => 'Jugador local 1', 'is_correct' => false],
                ['text' => 'Jugador local 2', 'is_correct' => false],
                ['text' => 'Jugador visitante 1', 'is_correct' => false],
                ['text' => 'Jugador visitante 2', 'is_correct' => false]
            ]
        ]);

        // Preguntas sociales
        TemplateQuestion::create([
            'type' => 'social',
            'text' => '¿Qué jugador del {{away_team}} marcará el próximo gol?',
            'options' => [
                ['text' => 'Jugador 1', 'is_correct' => false],
                ['text' => 'Jugador 2', 'is_correct' => false],
                ['text' => 'Jugador 3', 'is_correct' => false]
            ]
        ]);

        TemplateQuestion::create([
            'type' => 'social',
            'text' => '¿Qué jugador será el hombre del partido en el encuentro entre {{home_team}} y {{away_team}}?',
            'options' => [
                ['text' => 'Jugador 1', 'is_correct' => false],
                ['text' => 'Jugador 2', 'is_correct' => false],
                ['text' => 'Jugador 3', 'is_correct' => false]
            ]
        ]);

        TemplateQuestion::create([
            'type' => 'social',
            'text' => '¿Qué jugador recibirá la próxima tarjeta amarilla en el partido entre {{home_team}} y {{away_team}}?',
            'options' => [
                ['text' => 'Jugador 1', 'is_correct' => false],
                ['text' => 'Jugador 2', 'is_correct' => false],
                ['text' => 'Jugador 3', 'is_correct' => false]
            ]
        ]);

        TemplateQuestion::create([
            'type' => 'social',
            'text' => '¿Qué jugador del {{away_team}} hará el próximo pase de gol?',
            'options' => [
                ['text' => 'Jugador 1', 'is_correct' => false],
                ['text' => 'Jugador 2', 'is_correct' => false],
                ['text' => 'Jugador 3', 'is_correct' => false]
            ]
        ]);
    }
}
