<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QuestionException extends Exception
{
    protected $questionId;
    protected $userId;
    protected $reason;

    public const REASONS = [
        'already_answered' => 'Ya has respondido esta pregunta',
        'question_expired' => 'La pregunta ha expirado',
        'question_locked' => 'La pregunta está bloqueada para modificaciones',
        'invalid_answer' => 'Respuesta inválida',
        'question_not_found' => 'Pregunta no encontrada',
        'insufficient_permissions' => 'No tienes permisos para esta acción'
    ];

    public function __construct(
        string $message = "",
        int $questionId = null,
        int $userId = null,
        string $reason = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->questionId = $questionId;
        $this->userId = $userId;
        $this->reason = $reason;

        if (empty($message) && $reason && isset(self::REASONS[$reason])) {
            $message = self::REASONS[$reason];
        }

        parent::__construct($message, $code, $previous);
    }

    public function getQuestionId(): ?int
    {
        return $this->questionId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Question Error',
                'message' => $this->getMessage(),
                'question_id' => $this->questionId,
                'user_id' => $this->userId,
                'reason' => $this->reason
            ], 400);
        }

        return back()->with('error', $this->getMessage());
    }
}
