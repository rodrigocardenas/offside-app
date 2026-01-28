<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateWithSessionOrSanctum
{
    /**
     * Intenta autenticar al usuario con sesión de navegador o Bearer token (Sanctum)
     * Importante: las cookies deben ser enviadas con credentials: 'include' desde el cliente
     */
    public function handle(Request $request, Closure $next)
    {
        // Primero, intenta obtener el usuario con el Bearer token (para APIs)
        if ($this->hasBearerToken($request)) {
            try {
                Auth::shouldUse('sanctum');
                if (Auth::check()) {
                    \Illuminate\Support\Facades\Log::info("✅ Autenticado con Bearer token");
                    return $next($request);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("⚠️ Bearer token inválido: " . $e->getMessage());
            }
        }

        // Si no hay Bearer token, intenta con sesión (cookies)
        // Esto debería funcionar si credentials: 'include' fue enviado desde el cliente
        try {
            Auth::shouldUse('web');
            if (Auth::check()) {
                \Illuminate\Support\Facades\Log::info("✅ Autenticado con sesión de navegador");
                return $next($request);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("⚠️ Error verificando sesión: " . $e->getMessage());
        }

        // Si no está autenticado de ninguna forma, retorna 401
        \Illuminate\Support\Facades\Log::warning("❌ Intento de acceso no autenticado a {$request->path()}");
        
        return response()->json([
            'error' => 'Unauthenticated',
            'message' => 'No autenticado - intenta sesión o Bearer token',
        ], 401);
    }

    /**
     * Verifica si la petición tiene un Bearer token
     */
    private function hasBearerToken(Request $request): bool
    {
        $header = $request->header('Authorization', '');
        return strpos($header, 'Bearer ') === 0;
    }
}

