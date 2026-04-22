<?php

namespace App\Jobs;

use App\Models\TemplateQuestion;
use App\Models\Question;
use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateAnswersPoints implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $templateQuestion;

    /**
     * Create a new job instance.
     */
    public function __construct(TemplateQuestion $templateQuestion)
    {
        $this->templateQuestion = $templateQuestion;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $correctOption = collect($this->templateQuestion->options)
                ->first(function ($option) {
                    return $option['is_correct'] ?? false;
                });

            if (!$correctOption) {
                Log::warning('No se encontró una opción correcta para la pregunta', [
                    'template_question_id' => $this->templateQuestion->id
                ]);
                return;
            }

            $questions = Question::where('template_question_id', $this->templateQuestion->id)
                ->with(['answers.questionOption'])
                ->get();

            $syncedPointsCount = 0;

            foreach ($questions as $question) {
                foreach ($question->answers as $answer) {
                    $oldPointsEarned = $answer->points_earned;
                    $isCorrect = $answer->questionOption && $answer->questionOption->text === $correctOption['text'];
                    $newPointsEarned = $isCorrect ? $question->points : 0;

                    $answer->update([
                        'is_correct' => $isCorrect,
                        'points_earned' => $newPointsEarned,
                    ]);

                    // 🔧 Sincronizar puntos a group_user después de actualizar answers
                    $pointsDiff = $newPointsEarned - $oldPointsEarned;
                    if ($pointsDiff !== 0) {
                        $this->syncGroupUserPoints(
                            $answer->user_id,
                            $question->group_id,
                            $pointsDiff,
                            $question->id
                        );
                        $syncedPointsCount++;
                    }
                }
            }

            Log::info('Puntos actualizados y sincronizados exitosamente', [
                'template_question_id' => $this->templateQuestion->id,
                'synced_points_count' => $syncedPointsCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Error general en UpdateAnswersPoints', [
                'error' => $e->getMessage(),
                'template_question_id' => $this->templateQuestion->id,
                'trace' => $e->getTraceAsString()
            ]);

            // Relanzar la excepción para que Laravel la maneje
            throw $e;
        }
    }

    /**
     * Sincronizar cambios de puntos en answers → group_user.points
     *
     * @param int $userId
     * @param int $groupId
     * @param int $pointsDiff Diferencia de puntos (positiva o negativa)
     * @param int $questionId
     * @return void
     */
    private function syncGroupUserPoints(int $userId, int $groupId, int $pointsDiff, int $questionId = null): void
    {
        try {
            $group = Group::find($groupId);
            if (!$group) {
                Log::warning('Grupo no encontrado para sincronización de puntos', [
                    'group_id' => $groupId,
                    'user_id' => $userId,
                ]);
                return;
            }

            // Validar que el usuario sea miembro del grupo
            $isMember = $group->users()->where('user_id', $userId)->exists();
            if (!$isMember) {
                Log::warning('Usuario no es miembro del grupo para sincronización de puntos', [
                    'group_id' => $groupId,
                    'user_id' => $userId,
                ]);
                return;
            }

            // Obtener puntos actuales en group_user
            $currentPoints = DB::table('group_user')
                ->where('group_id', $groupId)
                ->where('user_id', $userId)
                ->value('points') ?? 0;

            $newPoints = max(0, $currentPoints + $pointsDiff); // Nunca permitir puntos negativos

            // Actualizar group_user.points
            DB::table('group_user')
                ->where('group_id', $groupId)
                ->where('user_id', $userId)
                ->update(['points' => $newPoints]);

            Log::info('Puntos sincronizados a group_user', [
                'user_id' => $userId,
                'group_id' => $groupId,
                'question_id' => $questionId,
                'old_points' => $currentPoints,
                'new_points' => $newPoints,
                'points_diff' => $pointsDiff,
            ]);

        } catch (\Exception $e) {
            Log::error('Error sincronizando puntos a group_user', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'group_id' => $groupId,
                'question_id' => $questionId,
                'points_diff' => $pointsDiff,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

