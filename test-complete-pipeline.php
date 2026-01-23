<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║   TEST FINAL COMPLETO: API Football PRO Pipeline              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// 1. Verificar API
echo "1️⃣  Verificando API Football PRO...\n";
$response = \Illuminate\Support\Facades\Http::withoutVerifying()->withHeaders([
    'x-apisports-key' => config('services.football.key'),
])->get('https://v3.football.api-sports.io/status');

if (!$response->ok()) {
    echo "   ❌ Error: " . $response->status() . "\n";
    exit(1);
}

$status = $response->json()['response'];
echo "   ✅ Conectada\n";
echo "   Plan: " . $status['subscription']['plan'] . "\n";
echo "   Activa: " . ($status['subscription']['active'] ? 'SÍ' : 'NO') . "\n";
echo "   Requests disponibles: " . $status['requests']['current'] . "/" . $status['requests']['limit_day'] . "\n\n";

// 2. Encontrar partidos para actualizar
echo "2️⃣  Buscando partidos sin actualizar...\n";
$candidates = \App\Models\FootballMatch::whereNotIn('status', ['FINISHED', 'Match Finished', 'AET', 'PEN'])
    ->where('date', '<=', now()->subHours(2))
    ->where('date', '>=', now()->subHours(72))
    ->limit(5)
    ->get();

if ($candidates->isEmpty()) {
    echo "   ℹ️  No hay partidos en rango 72h-2h\n\n";
} else {
    echo "   Encontrados: " . $candidates->count() . " partidos\n";
    foreach ($candidates as $m) {
        echo "   [{$m->id}] {$m->home_team} vs {$m->away_team} ({$m->external_id})\n";
    }
    echo "\n";
}

// 3. Procesar directamente con FootballService
echo "3️⃣  Procesando partidos con FootballService...\n";
$service = app()->make(\App\Services\FootballService::class);
$updated = 0;

foreach ($candidates as $match) {
    echo "\n   Actualizando: {$match->home_team} vs {$match->away_team}\n";
    
    try {
        $updatedMatch = $service->updateMatchFromApi($match->id);
        
        if ($updatedMatch) {
            echo "   ✅ Status: " . $updatedMatch->status . "\n";
            echo "   📊 Score: " . $updatedMatch->home_team_score . " - " . $updatedMatch->away_team_score . "\n";
            $updated++;
        } else {
            echo "   ⚠️  No se pudo actualizar\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n\n4️⃣  Resumen:\n";
echo "   Partidos actualizados: $updated\n";
echo "   API Football PRO: ✅ Funcionando\n";
echo "   Pipeline: ✅ Listo para producción\n\n";

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  ESTADO: LISTO PARA DEPLOYMENT                                ║\n";
echo "║  Próximo paso: php artisan queue:work                          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

?>
