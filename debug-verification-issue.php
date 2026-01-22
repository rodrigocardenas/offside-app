<?php

use App\Models\FootballMatch;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== VERIFICACIÓN DE PROBLEMAS ===\n\n";

// 1. Ver todos los partidos de ayer con preguntas
echo "1. PARTIDOS RECIENTES (últimas 48 horas):\n";
$twodays = now()->subDays(2)->startOfDay();
$allMatches = FootballMatch::where('date', '>=', $twodays)
    ->orderByDesc('date')
    ->get();

echo "   - Total de partidos (últimas 48 hs): {$allMatches->count()}\n\n";

$statusCounts = FootballMatch::where('date', '>=', $twodays)
    ->groupBy('status')
    ->selectRaw('status, count(*) as count')
    ->get();

echo "   STATUS DISTRIBUTION:\n";
foreach ($statusCounts as $stat) {
    echo "   - {$stat->status}: {$stat->count}\n";
}

echo "\n\n   DETALLES POR PARTIDO:\n";
foreach ($allMatches->take(10) as $match) {
    $unverified = $match->questions()->whereNull('result_verified_at')->count();
    $verified = $match->questions()->whereNotNull('result_verified_at')->count();

    echo "   - Match #{$match->id}: {$match->home_team} vs {$match->away_team}\n";
    echo "     Date: {$match->date} | Status: {$match->status}\n";
    echo "     Verified: {$verified} | Unverified: {$unverified}\n";
    echo "     Last verification attempt: " . ($match->last_verification_attempt_at ? $match->last_verification_attempt_at->diffForHumans() : 'Never') . "\n";
    echo "     Verification priority: {$match->verification_priority}\n\n";
}

echo "\n2. ANÁLISIS DE PREGUNTAS SIN VERIFICAR:\n";
$totalUnverified = Question::whereNull('result_verified_at')
    ->whereHas('football_match', function ($q) {
        $q->where('status', 'FINISHED');
    })
    ->count();

echo "   - Total de preguntas sin verificar (en partidos FINISHED): {$totalUnverified}\n";

// 3. Ver si hay jobs en la cola
echo "\n3. ESTADO DE LA COLA:\n";
$jobCount = DB::table('jobs')->count();
$failedCount = DB::table('failed_jobs')->count();
echo "   - Jobs en la cola: {$jobCount}\n";
echo "   - Failed jobs: {$failedCount}\n";

// Ver últimos failed jobs
if ($failedCount > 0) {
    echo "\n   Últimos failed jobs:\n";
    $failed = DB::table('failed_jobs')
        ->orderByDesc('id')
        ->limit(5)
        ->get();

    foreach ($failed as $job) {
        echo "   - ID {$job->id}: {$job->payload} (Failed at: {$job->failed_at})\n";
    }
}

// 4. Revisar last_verification_attempt_at vs cooldown
echo "\n4. ANÁLISIS DE COOLDOWN:\n";
$cooldown = 5; // minutos
$candidates = FootballMatch::where('date', '>=', $yesterday)
    ->where('status', 'FINISHED')
    ->whereHas('questions', function ($q) {
        $q->whereNull('result_verified_at');
    })
    ->get();

$readyToRetry = 0;
$stillCooling = 0;

foreach ($candidates as $match) {
    if (!$match->last_verification_attempt_at) {
        $readyToRetry++;
    } else {
        $minutesSince = $match->last_verification_attempt_at->diffInMinutes(now());
        if ($minutesSince >= $cooldown) {
            $readyToRetry++;
        } else {
            $stillCooling++;
        }
    }
}

echo "   - Listos para reintento: {$readyToRetry}\n";
echo "   - Enfriándose (cooldown): {$stillCooling}\n";

echo "\n✅ Diagnóstico completado.\n";
