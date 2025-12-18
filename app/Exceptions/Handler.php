<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        FootballApiException::class => 'warning',
        GroupAccessException::class => 'info',
        QuestionException::class => 'info',
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        GroupAccessException::class,
        QuestionException::class,
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'No autenticado'
                ], 401);
            }

            return redirect()->route('login');
        });

        $this->renderable(function (FootballApiException $e, Request $request) {
            return $e->render($request);
        });

        $this->renderable(function (GroupAccessException $e, Request $request) {
            return $e->render($request);
        });

        $this->renderable(function (QuestionException $e, Request $request) {
            return $e->render($request);
        });

        $this->renderable(function (ApplicationException $e, Request $request) {
            return $e->render($request);
        });

        $this->renderable(function (Throwable $e, Request $request) {
            // Enhanced error logging for unhandled exceptions
            Log::error('Unhandled Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Internal Server Error',
                    'message' => 'Ha ocurrido un error interno del servidor. Por favor, inténtalo de nuevo más tarde.',
                    'trace_id' => uniqid('error_')
                ], 500);
            }

            return response()->view('errors.500', [
                'message' => 'Ha ocurrido un error interno del servidor. Por favor, inténtalo de nuevo más tarde.',
                'trace_id' => uniqid('error_')
            ], 500);
        });
    }
}
