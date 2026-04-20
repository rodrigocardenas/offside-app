<?php

namespace App\Jobs;

use App\Models\Question;
use App\Models\Group;
use App\Services\GeminiService;
use App\Services\QuestionEvaluationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class VerifyAllQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900;
    public $tries = 3; // BUG #7 FIX: Retry on failure

    /** @var array<int> */
    protected array $matchIds;
    protected ?string $batchId;
    protected int $chunkSize;

    public function __construct(?array $matchIds = null, ?string $batchId = null, int $chunkSize = 50)
    {
        $this->matchIds = $matchIds ?? [];
        $this->batchId = $batchId;
        $this->chunkSize = $chunkSize;
    }

    public function handle(QuestionEvaluationService $evaluationService): void
    {
        Log::info('VerifyAllQuestionsJob started', [
            'batch_id' => $this->batchId,
            'match_ids' => $this->matchIds,
        ]);

        // ✅ OPTIMIZATION: Enable non-blocking mode to prevent long waits on rate limit
        GeminiService::setAllowBlocking(false);

        $processed = 0;
        $errors = 0;

        try {
            $query = Question::whereNull('result_verified_at')
                ->whereHas('football_match', function ($query) {
                    $query->whereIn('status', ['Match Finished', 'FINISHED', 'Finished']);
                })
                ->with(['football_match', 'options', 'answers']);

            if (!empty($this->matchIds)) {
                $query->whereIn('match_id', $this->matchIds);
            }

            $query->chunk($this->chunkSize, function ($questions) use ($evaluationService, &$processed, &$errors) {
                Log::info('VerifyAllQuestionsJob - processing chunk', [
                    'chunk_size' => $questions->count(),
                    'batch_id' => $this->batchId,
                ]);

                foreach ($questions as $question) {
                    try {
                        $this->processQuestion($question, $evaluationService);
                        $processed++;
                    } catch (Throwable $e) {
                        $errors++;
                        Log::error('VerifyAllQuestionsJob - failed to verify question', [
                            'question_id' => $question->id,
                            'batch_id' => $this->batchId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

            Log::info('VerifyAllQuestionsJob completed', [
                'batch_id' => $this->batchId,
                'processed_questions' => $processed,
                'errors' => $errors,
            ]);
        } catch (Throwable $e) {
            Log::error('VerifyAllQuestionsJob failed', [
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function processQuestion(Question $question, QuestionEvaluationService $evaluationService): void
    {
        $match = $question->football_match;

        if (!$match || !in_array($match->status, ['FINISHED', 'Match Finished', 'Finished'])) {
            Log::warning('VerifyAllQuestionsJob - match not ready for verification', [
                'question_id' => $question->id,
                'match_id' => $match?->id,
                'match_status' => $match?->status,
            ]);
            return;
        }

        $correctOptionIds = $evaluationService->evaluateQuestion($question, $match);

        // CRITICAL FIX: Don't mark as verified if evaluator returned empty array
        // This prevents "stuck" questions that failed evaluation from never being re-verified
        if (empty($correctOptionIds)) {
            Log::warning('VerifyAllQuestionsJob - evaluator returned no results (question will be re-tried)', [
                'question_id' => $question->id,
                'match_id' => $match->id,
            ]);
            return;
        }

        foreach ($question->options as $option) {
            $option->is_correct = in_array($option->id, $correctOptionIds);
            $option->save();
        }

        $updatedAnswers = 0;
        $synced_points_count = 0;

        foreach ($question->answers as $answer) {
            $wasCorrect = $answer->is_correct;
            $oldPointsEarned = $answer->points_earned;  // Capturar puntos anteriores
            
            $answer->is_correct = in_array($answer->question_option_id, $correctOptionIds);
            $answer->points_earned = $answer->is_correct ? ($question->points ?? 300) : 0;
            $answer->save();

            // 🔧 SINCRONIZAR group_user.points cuando cambian los puntos
            $pointsDiff = $answer->points_earned - $oldPointsEarned;
            if ($pointsDiff !== 0) {
                $this->syncGroupUserPoints(
                    $answer->user_id,
                    $question->group_id,
                    $pointsDiff,
                    $question->id
                );
                $synced_points_count++;
            }

            if ($wasCorrect !== $answer->is_correct) {
                $updatedAnswers++;
            }
        }

        $question->result_verified_at = now();
        $question->save();

        Log::info('VerifyAllQuestionsJob - question verified', [
            'question_id' => $question->id,
            'match_id' => $match->id,
            'answers_updated' => $updatedAnswers,
            'points_synced' => $synced_points_count,
        ]);
    }

    /**
     * 🔧 Sincronizar puntos de respuesta a tabla group_user
     * 
     * Cuando se verifica una respuesta y se asignan puntos, se debe actualizar
     * el acumulado en group_user.points para que los castigos (pre-match) y
     * rankings usen valores correctos.
     * 
     * @param int $userId Usuario que respondió
     * @param int $groupId Grupo donde se respondió la pregunta
     * @param int $pointsDiff Diferencia de puntos (puede ser positivo o negativo)
     * @param int $questionId ID de pregunta (para logging)
     */
    private function syncGroupUserPoints(int $userId, int $groupId, int $pointsDiff, int $questionId): void
    {
        try {
            $group = Group::find($groupId);
            if (!$group) {
                Log::warning('VerifyAllQuestionsJob - group not found for sync', [
                    'user_id' => $userId,
                    'group_id' => $groupId,
                    'question_id' => $questionId,
                ]);
                return;
            }

            // Obtener punto actual del usuario en el grupo
            $groupUser = $group->users()
                ->where('users.id', $userId)
                ->first();

            if (!$groupUser) {
                Log::warning('VerifyAllQuestionsJob - user not member of group', [
                    'user_id' => $userId,
                    'group_id' => $groupId,
                    'question_id' => $questionId,
                ]);
                return;
            }

            $currentPoints = $groupUser->pivot->points ?? 0;
            $newPoints = max(0, $currentPoints + $pointsDiff);  // No permitir puntos negativos

            // Actualizar el pivote
            $group->users()->updateExistingPivot($userId, [
                'points' => $newPoints
            ]);

            Log::info('VerifyAllQuestionsJob - points synced to group_user', [
                'user_id' => $userId,
                'group_id' => $groupId,
                'question_id' => $questionId,
                'points_diff' => $pointsDiff,
                'old_points' => $currentPoints,
                'new_points' => $newPoints,
            ]);
        } catch (Throwable $e) {
            Log::error('VerifyAllQuestionsJob - failed to sync points', [
                'user_id' => $userId,
                'group_id' => $groupId,
                'question_id' => $questionId,
                'points_diff' => $pointsDiff,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
