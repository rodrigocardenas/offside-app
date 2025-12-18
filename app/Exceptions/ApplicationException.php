<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApplicationException extends Exception
{
    protected $context;
    protected $errorCode;
    protected $httpCode;

    public function __construct(
        string $message = "",
        string $errorCode = null,
        array $context = [],
        int $httpCode = 500,
        ?\Throwable $previous = null
    ) {
        $this->context = $context;
        $this->errorCode = $errorCode;
        $this->httpCode = $httpCode;

        parent::__construct($message, 0, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode ?? 500;
    }

    public function render(Request $request): Response
    {
        $statusCode = $this->getHttpCode();
        $isDebug = config('app.debug');

        // En desarrollo, lanzar la excepción para ver detalles completos
        if ($isDebug) {
            throw $this;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'error' => $this->errorCode ?? 'Application Error',
                'message' => $this->getMessage(),
            ], $statusCode);
        }

        // Log the error for debugging
        Log::error('Application Exception', [
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'context' => $this->context,
            'user_id' => auth()->id(),
            'url' => $request->fullUrl(),
            'trace' => $this->getTraceAsString()
        ]);

        // En producción, mostrar mensajes genéricos
        if ($statusCode >= 500) {
            return response()->view('errors.500', [
                'message' => 'Ha ocurrido un error interno del servidor. Por favor, inténtalo de nuevo más tarde.',
                'error_code' => $this->errorCode
            ], 500);
        }

        return back()->with('error', $this->getMessage());
    }
}
