<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Group;
use App\Models\User;
use App\Notifications\PredictiveResultsAvailable;
use App\Notifications\NewPredictiveQuestionsAvailable;

class TestPredictiveNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:predictive-notifications {--type=results} {--group-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar notificaciones predictivas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $groupId = $this->option('group-id');

        if ($groupId) {
            $group = Group::find($groupId);
            if (!$group) {
                $this->error("Grupo con ID {$groupId} no encontrado.");
                return 1;
            }
        } else {
            $group = Group::first();
            if (!$group) {
                $this->error("No hay grupos disponibles.");
                return 1;
            }
        }

        $this->info("Probando notificaciones para el grupo: {$group->name}");

        if ($type === 'results') {
            $this->testResultsNotification($group);
        } elseif ($type === 'questions') {
            $this->testQuestionsNotification($group);
        } else {
            $this->error("Tipo de notificación no válido. Use 'results' o 'questions'");
            return 1;
        }

        $this->info("Notificaciones enviadas correctamente.");
        return 0;
    }

    private function testResultsNotification(Group $group)
    {
        $this->info("Enviando notificación de resultados...");

        // Simular estadísticas
        $correctAnswers = rand(1, 5);
        $totalAnswers = rand(5, 10);

        foreach ($group->users as $user) {
            $user->notify(new PredictiveResultsAvailable($group, $correctAnswers, $totalAnswers));
            $this->line("Notificación enviada a: {$user->name}");
        }
    }

    private function testQuestionsNotification(Group $group)
    {
        $this->info("Enviando notificación de nuevas preguntas...");

        $questionCount = rand(1, 5);

        foreach ($group->users as $user) {
            $user->notify(new NewPredictiveQuestionsAvailable($group, $questionCount));
            $this->line("Notificación enviada a: {$user->name}");
        }
    }
}
