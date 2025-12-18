<?php

namespace App\Exceptions;

use Exception;

class FootballApiException extends Exception
{
    protected $context;

    public function __construct(string $message = "", array $context = [], int $code = 0, ?\Throwable $previous = null)
    {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Football API Error',
                'message' => $this->getMessage(),
                'context' => $this->context
            ], 503);
        }

        return response()->view('errors.api', [
            'message' => $this->getMessage(),
            'context' => $this->context
        ], 503);
    }
}
