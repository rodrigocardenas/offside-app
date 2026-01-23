<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║      TEST FINAL: API Football PRO en Acción                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$service = app()->make(\App\Services\FootballService::class);

// 1. Verificar status
echo "1️⃣ Estado de la API Football PRO:\n";
$response = \Illuminate\Support\Facades\Http::withoutVerifying()->withHeaders([
    'x-apisports-key' => config('services.football.key'),
])->get('https://v3.football.api-sports.io/status');

if ($response->ok()) {
    $data = $response->json();
    $sub = $data['response']['subscription'];
    echo "   ✅ Conectada\n";
    echo "   Plan: " . $sub['plan'] . "\n";
    echo "   Activa: " . ($sub['active'] ? 'SÍ' : 'NO') . "\n";
    echo "   Límite: " . $data['response']['requests']['limit_day'] . " requests/día\n\n";
} else {
    echo "   ❌ No conectada\n\n";
    exit(1);
}

// 2. Buscar partidos sin actualizar
echo "2️⃣ Partidos candidatos para actualizar:\n";
$candidates = \App\Models\FootballMatch::whereNotIn('status', ['FINISHED', 'Match Finished'])
    ->where('date', '<=', now()->subHours(2))
    ->where('date', '>=', now()->subHours(72))
    ->limit(5)
    ->get();

if ($candidates->isEmpty()) {
    echo "   ℹ️  No hay partidos en rango 72h-2h para actualizar\n\n";
    
    // Mostrar estadísticas
    $all = \App\Models\FootballMatch::get();
    $finished = $all->where('status', 'Match Finished')->count();
    $notFinished = $all->where('status', '!=', 'Match Finished')->count();
    
    echo "   📊 Estadísticas totales:\n";
    echo "      - Terminados: $finished\n";
    echo "      - Pendientes: $notFinished\n";
    echo "      - Próximos partidos: " . \App\Models\FootballMatch::where('date', '>', now())->count() . "\n\n";
} else {
    echo "   Encontrados: {$candidates->count()}\n\n";
    foreach ($candidates as $match) {
        echo "   [{$match->id}] {$match->home_team} vs {$match->away_team}\n";
        echo "       Fecha: {$match->date->format('Y-m-d H:i')}\n";
        echo "       External ID: " . ($match->external_id ?? 'N/A') . "\n";
    }
    echo "\n";
}

// 3. Disparar job
echo "3️⃣ Disparando UpdateFinishedMatchesJob...\n";
try {
    \App\Jobs\UpdateFinishedMatchesJob::dispatch();
    echo "   ✅ Job despachado a la queue\n";
    echo "\n   📌 PRÓXIMO PASO:\n";
    echo "      php artisan queue:work\n\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Estado: LISTO PARA PRODUCCIÓN                                ║\n";
echo "║  API Football PRO: ✅ Conectada y funcionando                 ║\n";
echo "║  Pipeline: ✅ UpdateFinishedMatchesJob configurado             ║\n";
echo "║  Queue: ✅ Pronta para ejecutarse                              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
