<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Group;
use App\Traits\HandlesQuestions;
use Illuminate\Support\Facades\Log;

class CreatePredictiveQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesQuestions;

    public $timeout = 300; // 5 minutos
    public $tries = 3;

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info('Iniciando creación de preguntas predictivas');

        $groups = Group::with('competition')
            ->whereNotNull('competition_id')
            ->get();

        Log::info('Procesando ' . $groups->count() . ' grupos para nuevas preguntas predictivas');

        foreach ($groups as $group) {
            try {
                // Contar preguntas activas actuales
                $activeCount = $group->questions()
                    ->where('type', 'predictive')
                    ->where('available_until', '>', now())
                    ->count();

                Log::info("Grupo {$group->id} tiene {$activeCount} preguntas activas");

                // Solo crear nuevas preguntas si hay menos de 5 activas
                if ($activeCount < 5) {
                    // Usar el método del trait HandlesQuestions
                    $allQuestions = $this->fillGroupPredictiveQuestions($group);
                    $newQuestionsCount = $allQuestions->count() - $activeCount;

                    if ($newQuestionsCount > 0) {
                        // Notificar a los usuarios del grupo
                        \App\Jobs\SendNewPredictiveQuestionsPushNotification::dispatch($group->id, $newQuestionsCount);

                        Log::info("Nuevas preguntas predictivas creadas y notificación enviada al grupo {$group->id}", [
                            'new_questions_count' => $newQuestionsCount,
                            'total_active' => $allQuestions->count()
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error al procesar grupo ' . $group->id . ' para nuevas preguntas', [
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        Log::info('Finalizada creación de preguntas predictivas');
    }
}
