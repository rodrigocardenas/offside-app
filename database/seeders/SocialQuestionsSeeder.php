<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;
use App\Models\Group;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SocialQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        Log::info('Iniciando SocialQuestionsSeeder');

        $questions = [
            [
                'title' => '¿Quién sería el goleador del torneo?',
                'description' => 'De todos tus amigos en el grupo, ¿quién crees que marcaría más goles en un torneo?',
                'type' => 'social',
                'points' => 5,
                'available_until' => Carbon::now()->addDays(7),
            ],
            [
                'title' => '¿Quién sería el primero en ser expulsado?',
                'description' => 'Si el partido se pone picado, ¿quién de tus amigos sería el primero en recibir una tarjeta roja?',
                'type' => 'social',
                'points' => 5,
                'available_until' => Carbon::now()->addDays(7),
            ],
            [
                'title' => '¿Quién sería el mejor capitán?',
                'description' => '¿Quién de tus amigos tendría las mejores cualidades para ser capitán del equipo?',
                'type' => 'social',
                'points' => 5,
                'available_until' => Carbon::now()->addDays(7),
            ],
            [
                'title' => '¿Quién sería el mejor portero?',
                'description' => 'De todos tus amigos, ¿quién crees que se desempeñaría mejor como portero?',
                'type' => 'social',
                'points' => 5,
                'available_until' => Carbon::now()->addDays(7),
            ],
            [
                'title' => '¿Quién sería el mejor entrenador?',
                'description' => '¿Quién de tus amigos tendría las mejores cualidades para ser entrenador?',
                'type' => 'social',
                'points' => 5,
                'available_until' => Carbon::now()->addDays(7),
            ],
            [
                'title' => '¿Quién sería el mejor árbitro?',
                'description' => '¿Quién de tus amigos sería el más justo y ecuánime como árbitro?',
                'type' => 'social',
                'points' => 5,
                'available_until' => Carbon::now()->addDays(7),
            ],
            [
                'title' => '¿Quién sería el mejor comentarista?',
                'description' => '¿Quién de tus amigos sería el más entretenido como comentarista de fútbol?',
                'type' => 'social',
                'points' => 5,
                'available_until' => Carbon::now()->addDays(7),
            ],
            [
                'title' => '¿Quién sería el mejor hincha?',
                'description' => '¿Quién de tus amigos sería el hincha más apasionado y fiel?',
                'type' => 'social',
                'points' => 5,
                'available_until' => Carbon::now()->addDays(7),
            ],
            [
                'title' => '¿Quién sería el mejor director técnico?',
                'description' => '¿Quién de tus amigos sería el mejor para dirigir un equipo profesional?',
                'type' => 'social',
                'points' => 5,
                'available_until' => Carbon::now()->addDays(7),
            ],
            [
                'title' => '¿Quién sería el mejor representante?',
                'description' => '¿Quién de tus amigos sería el mejor representante de jugadores?',
                'type' => 'social',
                'points' => 5,
                'available_until' => Carbon::now()->addDays(7),
            ],
        ];

        // Obtener todos los grupos
        $groups = Group::all();
        Log::info('Grupos encontrados: ' . $groups->count());

        foreach ($groups as $group) {
            Log::info('Procesando grupo: ' . $group->id);

            // Obtener los usuarios del grupo
            $users = $group->users;
            Log::info('Usuarios en el grupo: ' . $users->count());

            // Solo crear preguntas si hay al menos 2 usuarios en el grupo
            if ($users->count() >= 2) {
                foreach ($questions as $questionData) {
                    try {
                        $question = Question::create([
                            'title' => $questionData['title'],
                            'description' => $questionData['description'],
                            'type' => $questionData['type'],
                            'points' => $questionData['points'],
                            'available_until' => $questionData['available_until'],
                            'group_id' => $group->id,
                        ]);

                        Log::info('Pregunta creada: ' . $question->id);

                        // Crear una opción para cada usuario del grupo
                        foreach ($users as $user) {
                            $question->options()->create([
                                'text' => $user->name,
                                'is_correct' => false,
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Error al crear pregunta: ' . $e->getMessage());
                    }
                }
            } else {
                Log::info('El grupo no tiene suficientes usuarios para crear preguntas');
            }
        }

        Log::info('SocialQuestionsSeeder completado');
    }
}
