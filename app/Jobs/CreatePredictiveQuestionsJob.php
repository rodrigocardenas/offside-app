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
     *
     * OPTIMIZACIÓN: Utiliza chunking para procesar grupos de manera más eficiente
     * sin sobrecargar la memoria ni la base de datos.
     */
    public function handle()
    {
        Log::info('Iniciando creación de preguntas predictivas (chunking)');

        $chunkSize = 50; // Procesar 50 grupos a la vez
        $totalGroupsProcessed = 0;
        $totalQuestionsCreated = 0;

        Group::with('competition')
            ->whereNotNull('competition_id')
            ->chunk($chunkSize, function ($groups) use (&$totalGroupsProcessed, &$totalQuestionsCreated) {

                Log::info('Procesando chunk de ' . $groups->count() . ' grupos');

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
                                // DESACTIVADO: Notificaciones de nuevas preguntas
                                // Ahora se envía un reminder diario de preguntas sin responder en lugar de notificar cada pregunta nueva
                                // \App\Jobs\SendNewPredictiveQuestionsPushNotification::dispatch($group->id, $newQuestionsCount);

                                Log::info("Nuevas preguntas predictivas creadas para el grupo {$group->id}", [
                                    'new_questions_count' => $newQuestionsCount,
                                    'total_active' => $allQuestions->count()
                                ]);

                                $totalQuestionsCreated += $newQuestionsCount;
                            }
                        }

                        $totalGroupsProcessed++;

                    } catch (\Exception $e) {
                        Log::error('Error al procesar grupo ' . $group->id . ' para nuevas preguntas', [
                            'error' => $e->getMessage()
                        ]);
                        continue;
                    }
                }

                Log::info('Chunk completado', [
                    'groups_processed' => $groups->count(),
                    'total_groups' => $totalGroupsProcessed,
                    'total_questions_created' => $totalQuestionsCreated
                ]);
            });

        Log::info('Finalizada creación de preguntas predictivas', [
            'total_groups_processed' => $totalGroupsProcessed,
            'total_questions_created' => $totalQuestionsCreated
        ]);
    }
}
