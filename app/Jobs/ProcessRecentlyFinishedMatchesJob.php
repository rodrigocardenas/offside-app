<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\FootballMatch;
use App\Models\Question;
use App\Models\Group;
use App\Services\FootballService;
use App\Services\OpenAIService;
use App\Traits\HandlesQuestions;
use Illuminate\Support\Facades\Log;

class ProcessRecentlyFinishedMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HandlesQuestions;

    /**
     * Execute the job.
     */
    public function handle(FootballService $footballService, OpenAIService $openAIService)
    {
        Log::info('Iniciando procesamiento de partidos finalizados recientemente');

        // 1. Verificar resultados de preguntas de partidos finalizados recientemente
        $this->verifyRecentlyFinishedQuestions($footballService, $openAIService);

        // 2. Crear nuevas preguntas predictivas y notificar usuarios
        $this->createNewPredictiveQuestions();
    }

    /**
     * Verifica resultados de preguntas de partidos finalizados recientemente
     */
    private function verifyRecentlyFinishedQuestions(FootballService $footballService, OpenAIService $openAIService)
    {
        // Obtener preguntas pendientes de partidos que deberían haber terminado
        // Usamos la fecha del partido + 2 horas de margen para asegurar que terminó
        $pendingQuestions = Question::whereNull('result_verified_at')
            ->whereHas('football_match', function($query) {
                $query->where('date', '<=', now()->subHours(2)); // Partido terminado hace al menos 2 horas
            })
            ->get();

        Log::info('Preguntas pendientes de verificación encontradas: ' . $pendingQuestions->count());

        foreach ($pendingQuestions as $question) {
            try {
                $match = $question->football_match;
                $answers = $question->answers;

                // Verificar resultados usando OpenAI (reutilizando lógica del Job original)
                $correctAnswers = $openAIService->verifyMatchResults(
                    [
                        'homeTeam' => $match->home_team,
                        'awayTeam' => $match->away_team,
                        'score' => $match->score,
                        'events' => $match->events
                    ],
                    [
                        [
                            'title' => $question->title,
                            'options' => $question->options->pluck('text')->toArray()
                        ]
                    ]
                );

                // Actualizar las respuestas correctas
                foreach ($answers as $answer) {
                    $answer->is_correct = in_array($answer->option_id, $correctAnswers->toArray());
                    $answer->points_earned = $answer->is_correct ? 300 : 0;
                    $answer->save();
                }

                // Marcar la pregunta como verificada
                $question->result_verified_at = now();
                $question->save();

                Log::info('Pregunta verificada correctamente: ' . $question->id, [
                    'match_date' => $match->date,
                    'match_teams' => $match->home_team . ' vs ' . $match->away_team
                ]);

            } catch (\Exception $e) {
                Log::error('Error al verificar resultados para la pregunta ' . $question->id, [
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }
    }

    /**
     * Crea nuevas preguntas predictivas y notifica a los usuarios
     */
    private function createNewPredictiveQuestions()
    {
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
                    // Usar el método del trait HandlesQuestions (reutilizando lógica existente)
                    $newQuestions = $this->fillGroupPredictiveQuestions($group);

                    if ($newQuestions->count() > 0) {
                        // Notificar a los usuarios del grupo
                        \App\Jobs\SendNewPredictiveQuestionsPushNotification::dispatch($group->id, $newQuestions->count());

                        Log::info("Nuevas preguntas predictivas creadas y notificación enviada al grupo {$group->id}", [
                            'new_questions_count' => $newQuestions->count(),
                            'total_active' => $activeCount + $newQuestions->count()
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
    }
}
