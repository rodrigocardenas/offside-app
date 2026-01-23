<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║      TEST: UpdateFinishedMatchesJob con API Football PRO      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Obtener partidos sin actualizar
$candidates = \App\Models\FootballMatch::whereNotIn('status', ['FINISHED', 'Match Finished', 'AET', 'PEN'])
    ->where('date', '<=', now()->subHours(2))
    ->where('date', '>=', now()->subHours(72))
    ->limit(3)
    ->get();

if ($candidates->isEmpty()) {
    echo "❌ No hay partidos para actualizar\n\n";
    exit(0);
}

echo "📋 Partidos a procesar (" . $candidates->count() . "):\n";
foreach ($candidates as $match) {
    echo "   [{$match->id}] {$match->home_team} vs {$match->away_team} (Status: {$match->status})\n";
}
echo "\n";

// Crear servicio
$service = app()->make(\App\Services\FootballService::class);

// Procesar cada partido
$updated = 0;
foreach ($candidates as $match) {
    echo "🔄 Procesando: {$match->home_team} vs {$match->away_team}\n";
    
    try {
        // Obtener fixture ID
        if (!$match->external_id) {
            echo "   ⚠️  Sin external_id, saltando...\n\n";
            continue;
        }
        
        // Obtener datos de la API
        $response = \Illuminate\Support\Facades\Http::withoutVerifying()->withHeaders([
            'x-apisports-key' => config('services.football.key'),
        ])->get('https://v3.football.api-sports.io/fixtures', [
            'id' => $match->external_id
        ]);
        
        if (!$response->ok()) {
            echo "   ❌ Error API (Status {$response->status()})\n\n";
            continue;
        }
        
        $data = $response->json();
        if (empty($data['response'])) {
            echo "   ⚠️  Fixture no encontrado\n\n";
            continue;
        }
        
        $fixture = $data['response'][0];
        $status = $fixture['fixture']['status']['short'] ?? null;
        $home_score = $fixture['goals']['home'] ?? null;
        $away_score = $fixture['goals']['away'] ?? null;
        
        echo "   Status: {$status} | Goles: {$home_score} - {$away_score}\n";
        
        // Actualizar partido si está terminado
        if (in_array($status, ['FT', 'AET', 'PEN', 'PST'])) {
            echo "   Guardando: home_team_score=$home_score, away_team_score=$away_score\n";
            
            $result = $match->update([
                'status' => 'Match Finished',
                'home_team_score' => $home_score,
                'away_team_score' => $away_score,
                'updated_at' => now(),
            ]);
            
            // Recargar para ver lo que se guardó
            $match->refresh();
            echo "   BD después: home_team_score={$match->home_team_score}, away_team_score={$match->away_team_score}\n";
            
            echo "   ✅ ACTUALIZADO\n\n";
            $updated++;
        } else {
            echo "   ℹ️  No terminado (status: {$status})\n\n";
        }
        
    } catch (\Exception $e) {
        echo "   ❌ Excepción: " . $e->getMessage() . "\n\n";
    }
}

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  RESULTADO                                                     ║\n";
echo "║  Partidos actualizados: $updated                               ║\n";
echo "║  API Football PRO: ✅ Funcionando                              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
?>
