<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateWithSessionOrSanctum
{
    /**
     * Intenta autenticar al usuario con sesión o Bearer token (Sanctum)
     */
    public function handle(Request $request, Closure $next)
    {
        // Intenta primero con sesión (web guard)
        if (Auth::guard('web')->check()) {
            Auth::shouldUse('web');
            return $next($request);
        }

        // Si no hay sesión, intenta con Sanctum (Bearer token)
        if (Auth::guard('sanctum')->check()) {
            Auth::shouldUse('sanctum');
            return $next($request);
        }

        // Si no está autenticado de ninguna forma
        return response()->json([
            'error' => 'Unauthenticated',
            'message' => 'No autenticado - intenta sesión o Bearer token',
        ], 401);
    }
}
