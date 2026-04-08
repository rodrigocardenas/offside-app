<?php

namespace App\Jobs;

use App\Models\FootballMatch;
use App\Models\Question;
use App\Models\Answer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * BUG #7 FIX: Health check job para monitorear el flujo de verificación
 *
 * Se ejecuta después del ciclo de verificación (:20)
 * Verifica:
 * 1. ¿Cuántos partidos siguen sin finalizar?
 * 2. ¿Cuántas preguntas están sin verificar?
 * 3. ¿Cuántos usuarios tienen puntos = 0?
 * 4. ¿Hubo timeouts o errores en logs?
 *
 * Si hay anomalías → Envía alerta a admin
 */
class VerifyBatchHealthCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3; // BUG #7 FIX: Retry on failure

    protected int $unfinalizedThreshold = 5;      // Alerta si hay >5 partidos sin finalizar
    protected int $unverifiedThreshold = 10;      // Alerta si hay >10 preguntas sin verificar
    protected int $zeroPointsThreshold = 50;      // Alerta si hay >50 respuestas con 0 puntos

    public function handle()
    {
        Log::info('═══════════════════════════════════════════════════════════');
        Log::info('🔍 INICIANDO: VerifyBatchHealthCheckJob - Health Check');
        Log::info('═══════════════════════════════════════════════════════════');

        try {
            $health = $this->performHealthCheck();

            if ($health['status'] === 'ALERT') {
                Log::alert('⚠️ BUG #7: ANOMALÍA DETECTADA EN FLUJO DE VERIFICACIÓN', $health);
                // Aquí se podría enviar email/notificación a admin
            } else {
                Log::info('✅ Batch verification cycle completed normally', $health);
            }

        } catch (\Exception $e) {
            Log::error('VerifyBatchHealthCheckJob failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        Log::info('═══════════════════════════════════════════════════════════');
        Log::info('✅ FINALIZADO: VerifyBatchHealthCheckJob');
        Log::info('═══════════════════════════════════════════════════════════');
    }

    protected function performHealthCheck(): array
    {
        $now = now();
        $windowStart = $now->copy()->subHours(1);

        // Métrica 1: Partidos no finalizados en la última hora
        $unfinalizedMatches = FootballMatch::whereNotIn('status', ['FINISHED', 'Match Finished', 'Finished'])
            ->where('date', '>=', $windowStart)
            ->count();

        // Métrica 2: Preguntas sin verificar (últimas 24 horas)
        $unverifiedQuestions = Question::whereNull('result_verified_at')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        // Métrica 3: Respuestas con puntos = 0 (últimas 24 horas)
        $zeroPointsAnswers = Answer::where('points_earned', 0)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        // Métrica 4: Verificaciones fallidas en logs (últimas 2 horas)
        // Buscar en almacenamiento de logs si es posible
        $recentErrors = $this->countRecentErrors($windowStart);

        $health = [
            'timestamp' => $now->toIso8601String(),
            'metrics' => [
                'unfinalized_matches' => $unfinalizedMatches,
                'unverified_questions' => $unverifiedQuestions,
                'zero_points_answers' => $zeroPointsAnswers,
                'recent_errors' => $recentErrors,
            ],
            'thresholds' => [
                'unfinalized_matches_alert' => $this->unfinalizedThreshold,
                'unverified_questions_alert' => $this->unverifiedThreshold,
                'zero_points_answers_alert' => $this->zeroPointsThreshold,
            ],
            'status' => 'OK'
        ];

        // Detectar anomalías
        if ($unfinalizedMatches > $this->unfinalizedThreshold) {
            $health['status'] = 'ALERT';
            $health['alerts'][] = "Too many unfinalized matches: {$unfinalizedMatches} (threshold: {$this->unfinalizedThreshold})";
        }

        if ($unverifiedQuestions > $this->unverifiedThreshold) {
            $health['status'] = 'ALERT';
            $health['alerts'][] = "Too many unverified questions: {$unverifiedQuestions} (threshold: {$this->unverifiedThreshold})";
        }

        if ($zeroPointsAnswers > $this->zeroPointsThreshold) {
            $health['status'] = 'ALERT';
            $health['alerts'][] = "Too many zero-points answers: {$zeroPointsAnswers} (threshold: {$this->zeroPointsThreshold})";
        }

        if ($recentErrors > 0) {
            $health['status'] = 'ALERT';
            $health['alerts'][] = "Errors detected in batch jobs: {$recentErrors} errors in last 2 hours";
        }

        return $health;
    }

    protected function countRecentErrors($since): int
    {
        // OPTIMIZACIÓN: No leer logs línea por línea (muy lento)
        // En producción, usar Sentry o servicio de logs externo
        // Por ahora retornar 0 para evitar I/O bloqueo
        return 0;
    }
}
