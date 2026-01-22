<?php

use App\Models\FootballMatch;
use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ANÁLISIS DETALLADO DEL PROBLEMA ===\n\n";

// 1. Partidos que DEBERÍAN ser procesados
echo "1. PARTIDOS QUE DEBERÍAN SER VERIFICADOS:\n";

// Simulemos los criterios del job
$windowHours = 12;
$windowStart = now()->subHours($windowHours);

$shouldBeCandidates = FootballMatch::query()
    ->withCount(['questions as pending_questions_count' => function ($query) {
        $query->whereNull('result_verified_at');
    }])
    ->whereIn('status', ['Match Finished', 'FINISHED'])
    ->where('date', '>=', $windowStart)
    ->whereHas('questions', function ($query) {
        $query->whereNull('result_verified_at');
    })
    ->get();

echo "   - Partidos FINISHED en últimas {$windowHours} horas con preguntas sin verificar: {$shouldBeCandidates->count()}\n";

// 2. ¿Por qué no hay partidos FINISHED?
echo "\n2. ¿POR QUÉ NO HAY PARTIDOS FINISHED?\n";

$recentMatches = FootballMatch::where('date', '>=', now()->subDays(7))->count();
$allStatuses = FootballMatch::select('status')->distinct()->pluck('status');

echo "   - Total de partidos en últimos 7 días: {$recentMatches}\n";
echo "   - Status encontrados en BD: " . implode(', ', $allStatuses->toArray()) . "\n";

// 3. Verificar si el problema es que los partidos no se actualizan desde la API
echo "\n3. VERIFICACIÓN DE ÚLTIMA ACTUALIZACIÓN DE PARTIDOS:\n";

$lastUpdated = FootballMatch::where('date', '>=', now()->subDays(7))
    ->orderByDesc('updated_at')
    ->first();

if ($lastUpdated) {
    echo "   - Último partido actualizado: {$lastUpdated->updated_at->diffForHumans()}\n";
    echo "   - ID: {$lastUpdated->id} ({$lastUpdated->home_team} vs {$lastUpdated->away_team})\n";
    echo "   - Status: {$lastUpdated->status}\n";
} else {
    echo "   - No hay partidos actualizados recientemente!\n";
}

// 4. Ver jobs que deberían estar ejecutándose
echo "\n4. JOBS QUE DEBERÍAN ESTAR EJECUTÁNDOSE:\n";

$jobs = DB::table('jobs')->count();
$batches = DB::table('job_batches')->where('finished_at', null)->count();
$failedJobs = DB::table('failed_jobs')->count();

echo "   - Jobs pendientes en la cola: {$jobs}\n";
echo "   - Batches activos: {$batches}\n";
echo "   - Failed jobs: {$failedJobs}\n";

// 5. Últimos eventos de monitoreo
echo "\n5. ÚLTIMOS EVENTOS DE VERIFICACIÓN:\n";

$monitoringRuns = DB::table('job_monitoring_runs')
    ->orderByDesc('started_at')
    ->limit(10)
    ->get();

if ($monitoringRuns->count() > 0) {
    foreach ($monitoringRuns as $run) {
        echo "   - {$run->job_class} (Batch: {$run->batch_id})\n";
        echo "     Iniciado: {$run->started_at} | Status: {$run->status}\n";
    }
} else {
    echo "   - No hay eventos de monitoreo registrados\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "RECOMENDACIONES:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if ($recentMatches === 0) {
    echo "❌ NO HAY DATOS EN LA BASE DE DATOS\n";
    echo "   → Necesitas ejecutar una sincronización de equipos/partidos\n";
    echo "   → Ejecuta: php artisan sync:football-data\n\n";
}

if ($lastUpdated && $lastUpdated->updated_at->diffInHours(now()) > 24) {
    echo "❌ LOS PARTIDOS NO SE ACTUALIZAN DESDE LA API\n";
    echo "   → El servicio de actualización no está ejecutándose\n";
    echo "   → Verifica que haya un scheduler activo\n";
    echo "   → Ejecuta: php artisan schedule:work\n\n";
}

if ($jobs === 0 && $recentMatches > 0) {
    echo "❌ NO HAY JOBS EN LA COLA\n";
    echo "   → El scheduler no está encolando trabajos\n";
    echo "   → Ejecuta manualmente: php artisan jobs:verify-finished-matches\n\n";
}

echo "✅ Diagnóstico completado.\n";
