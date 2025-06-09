<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Group;
use App\Notifications\NewSocialQuestionAvailable;
use Carbon\Carbon;

class RenewSocialQuestions extends Command
{
    protected $signature = 'social-questions:renew';
    protected $description = 'Renueva las preguntas sociales para todos los grupos activos';

    public function handle()
    {
        $groups = Group::all();

        foreach ($groups as $group) {
            // Eliminar preguntas sociales antiguas
            $group->questions()
                ->where('type', 'social')
                ->where('available_until', '<', now())
                ->delete();

            // Crear nueva pregunta social
            $socialQuestion = $this->createSocialQuestion($group);

            if ($socialQuestion) {
                // Notificar a todos los usuarios del grupo
                foreach ($group->users as $user) {
                    $user->notify(new NewSocialQuestionAvailable($socialQuestion));
                }
            }
        }

        $this->info('Preguntas sociales renovadas exitosamente.');
    }

    protected function createSocialQuestion($group)
    {
        $group->load('users');

        if ($group->users->count() < 2) {
            return null;
        }

        return \App\Models\Question::create([
            'title' => '¿Quién será el MVP del grupo hoy?',
            'description' => 'Vota por el miembro que crees que tendrá el mejor desempeño hoy',
            'type' => 'social',
            'points' => 100,
            'group_id' => $group->id,
            'available_until' => now()->addDay(),
        ]);
    }
}
