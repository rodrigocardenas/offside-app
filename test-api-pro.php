<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         TEST: API Football PRO + UpdateFinishedMatchesJob     ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// 1. Verificar API Key
$apiKey = config('services.football.key');
echo "1️⃣ API Key configurada: " . substr($apiKey, -10) . "\n";
echo "   Longitud: " . strlen($apiKey) . "\n\n";

// 2. Test directo a API
echo "2️⃣ Probando conexión a API Football...\n";
$response = \Illuminate\Support\Facades\Http::withoutVerifying()->withHeaders([
    'x-apisports-key' => $apiKey,
])->get('https://v3.football.api-sports.io/status');

echo "   Status: " . $response->status() . "\n";
echo "   Body: " . substr($response->body(), 0, 200) . "...\n";

if ($response->status() === 200) {
    $data = $response->json();
    if (isset($data['response']['subscription'])) {
        $sub = $data['response']['subscription'];
        echo "   ✅ API CONECTADA\n";
        echo "   Plan: " . $sub['plan'] . "\n";
        echo "   Activa: " . ($sub['active'] ? 'SÍ' : 'NO') . "\n";
        echo "   Límite diario: " . $data['response']['requests']['limit_day'] . " requests\n\n";
    } else {
        echo "   ⚠️ No se pudo obtener info de suscripción\n\n";
    }
} else {
    echo "   ❌ Error: " . $response->status() . "\n\n";
    exit(1);
}

// 3. Buscar partidos para actualizar
echo "3️⃣ Buscando partidos sin actualizar...\n";
$candidates = \App\Models\FootballMatch::whereNotIn('status', ['FINISHED', 'Match Finished'])
    ->where('date', '<=', now()->subHours(2))
    ->where('date', '>=', now()->subHours(24))
    ->limit(5)
    ->get();

echo "   Encontrados: {$candidates->count()} partidos\n\n";

if ($candidates->isEmpty()) {
    echo "   ⚠️  No hay partidos para actualizar\n";
    exit(0);
}

// 4. Mostrar partidos
echo "4️⃣ Partidos a actualizar:\n";
foreach ($candidates as $match) {
    echo "   [{$match->id}] {$match->home_team} vs {$match->away_team}\n";
    echo "       Fecha: {$match->date->format('Y-m-d H:i')}\n";
    echo "       Status: {$match->status}\n";
}

echo "\n5️⃣ Disparando UpdateFinishedMatchesJob...\n";
\App\Jobs\UpdateFinishedMatchesJob::dispatch();

echo "   ✅ Job despachado a la queue\n";
echo "   Ejecuta: php artisan queue:work\n";
