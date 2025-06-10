<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Group;
use App\Notifications\NewSocialQuestionAvailable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

        // Obtener una pregunta social aleatoria que no haya sido usada en este grupo
        $templateQuestion = \App\Models\TemplateQuestion::where('type', 'social')
            ->where(function ($query) use ($group) {
                $query->whereNull('used_at')
                    ->orWhereNotExists(function ($subquery) use ($group) {
                        $subquery->select(\DB::raw(1))
                            ->from('questions')
                            ->whereColumn('questions.template_question_id', 'template_questions.id')
                            ->where('questions.group_id', $group->id);
                    });
            })
            ->inRandomOrder()
            ->first();

        if (!$templateQuestion) {
            return null;
        }

        // Crear la pregunta basada en la plantilla
        $question = \App\Models\Question::create([
            'title' => $templateQuestion->text,
            'description' => $templateQuestion->text,
            'type' => 'social',
            'points' => 100,
            'group_id' => $group->id,
            'available_until' => now()->addDay(),
            'template_question_id' => $templateQuestion->id
        ]);

        // Crear opciones basadas en los usuarios del grupo
        foreach ($group->users as $user) {
            \App\Models\QuestionOption::create([
                'question_id' => $question->id,
                'text' => $user->name,
                'is_correct' => false
            ]);
        }

        // Marcar la plantilla como usada
        // $templateQuestion->update(['used_at' => now()]);

        return $question;
    }
}
