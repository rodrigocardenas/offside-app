<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\AnomalyDetectionService;

class RateLimitUserCreation
{
    protected $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $username = trim($request->input('name', 'unknown'));

        // Verificar si la IP está bloqueada por detección de anomalías
        if (AnomalyDetectionService::shouldBlockIp($ip)) {
            Log::warning('Blocked request from IP', [
                'ip' => $ip,
                'username' => $username,
                'reason' => 'IP blocked by anomaly detection',
            ]);

            return response()->json([
                'error' => 'Esta IP ha sido bloqueada por comportamiento sospechoso detectado',
                'retry_after' => 86400,
            ], 429);
        }

        // Clave para rate limiting por IP
        $ipKey = 'login_attempts_' . $ip;
        $usernameKey = 'login_username_' . $username . '_' . $ip;

        // Límite 1: Máximo 10 intentos de login por IP por minuto
        if ($this->limiter->tooManyAttempts($ipKey, 10)) {
            Log::warning('Rate limit exceeded for IP', [
                'ip' => $ip,
                'username' => $username,
                'user_agent' => $userAgent,
                'timestamp' => now(),
            ]);

            $this->triggerSecurityAlert($ip, $username, $userAgent, 'TOO_MANY_ATTEMPTS');

            return response()->json([
                'error' => 'Demasiados intentos de login. Intenta de nuevo en 1 minuto.',
                'retry_after' => 60,
            ], 429);
        }

        // Límite 2: Máximo 3 creaciones del MISMO username por IP en 5 minutos
        $usernameAttempts = Cache::get($usernameKey, 0);
        if ($usernameAttempts >= 3) {
            Log::warning('Same username spam detected', [
                'ip' => $ip,
                'username' => $username,
                'attempts' => $usernameAttempts,
                'user_agent' => $userAgent,
                'timestamp' => now(),
            ]);

            $this->triggerSecurityAlert($ip, $username, $userAgent, 'DUPLICATE_USERNAME');

            return response()->json([
                'error' => 'Este usuario ha sido creado demasiadas veces recientemente. Intenta con otro nombre.',
                'blocked_until' => now()->addMinutes(5)->toDateTimeString(),
            ], 429);
        }

        // Límite 3: Máximo 20 creaciones de usuarios POR IP en 1 hora
        $totalKey = 'login_total_' . $ip;
        $totalAttempts = Cache::get($totalKey, 0);
        if ($totalAttempts >= 20) {
            Log::warning('Suspicious activity: Too many user creations from IP', [
                'ip' => $ip,
                'total_attempts' => $totalAttempts,
                'user_agent' => $userAgent,
                'timestamp' => now(),
            ]);

            $this->triggerSecurityAlert($ip, $username, $userAgent, 'TOO_MANY_HOURLY');

            return response()->json([
                'error' => 'Tu IP ha creado demasiados usuarios. Intenta nuevamente más tarde.',
                'blocked_until' => now()->addHours(1)->toDateTimeString(),
            ], 429);
        }

        // Ejecutar detección de anomalías (después de pasar los límites básicos)
        $anomalies = AnomalyDetectionService::detectSuspiciousActivity($ip, $username, $userAgent);
        if (!empty($anomalies)) {
            // Si se detectan anomalías críticas, bloquear la IP
            $criticalAnomalies = array_filter($anomalies, fn($a) => $a['severity'] === 'CRITICAL');
            if (!empty($criticalAnomalies)) {
                AnomalyDetectionService::blockIp($ip, 'CRITICAL anomaly detected');

                return response()->json([
                    'error' => 'Comportamiento sospechoso detectado. Acceso denegado.',
                    'retry_after' => 86400,
                ], 429);
            }
        }

        // Pasar al siguiente middleware/controlador
        $response = $next($request);

        // Si fue exitoso (status 200), incrementar contadores
        if ($response->getStatusCode() < 400) {
            // Incrementar contador general por IP (1 hora)
            Cache::put($ipKey, $this->limiter->attempts($ipKey) + 1, 60);
            Cache::put($totalKey, $totalAttempts + 1, 3600); // 1 hora
            Cache::put($usernameKey, $usernameAttempts + 1, 300); // 5 minutos

            Log::info('User creation logged', [
                'ip' => $ip,
                'username' => $username,
                'total_from_ip' => $totalAttempts + 1,
                'user_agent' => $userAgent,
            ]);
        }

        return $response;
    }

    private function triggerSecurityAlert($ip, $username, $userAgent, $reason)
    {
        Log::channel('security')->warning('Security threshold exceeded', [
            'ip' => $ip,
            'username' => $username,
            'user_agent' => $userAgent,
            'reason' => $reason,
            'timestamp' => now(),
        ]);
    }
}
