<?php

namespace App\Jobs;

use App\Models\Question;
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
    public $tries = 1;

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
                    $query->whereIn('status', ['FINISHED', 'Match Finished']);
                })
                ->with(['football_match', 'options', 'answers']);

            if (!empty($this->matchIds)) {
                $query->whereIn('football_match_id', $this->matchIds);
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

        if (!$match || !in_array($match->status, ['FINISHED', 'Match Finished'])) {
            Log::warning('VerifyAllQuestionsJob - match not ready for verification', [
                'question_id' => $question->id,
                'match_id' => $match?->id,
                'match_status' => $match?->status,
            ]);
            return;
        }

        $correctOptionIds = $evaluationService->evaluateQuestion($question, $match);

        foreach ($question->options as $option) {
            $option->is_correct = in_array($option->id, $correctOptionIds);
            $option->save();
        }

        $updatedAnswers = 0;

        foreach ($question->answers as $answer) {
            $wasCorrect = $answer->is_correct;
            $answer->is_correct = in_array($answer->question_option_id, $correctOptionIds);
            $answer->points_earned = $answer->is_correct ? ($question->points ?? 300) : 0;
            $answer->save();

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
        ]);
    }
}
