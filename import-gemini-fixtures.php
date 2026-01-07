<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\GeminiService;
use App\Models\FootballMatch;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "\n════════════════════════════════════════════════════════════════\n";
echo "📥 IMPORTANDO FIXTURES DE GEMINI A LA BASE DE DATOS\n";
echo "════════════════════════════════════════════════════════════════\n\n";

try {
    $service = app(GeminiService::class);
    
    echo "🔍 Obteniendo fixtures de Gemini...\n";
    $fixtures = $service->getFixtures('La Liga', forceRefresh: true);
    
    if (!$fixtures || !isset($fixtures['matches'])) {
        echo "❌ No se obtuvieron partidos\n";
        exit(1);
    }
    
    echo "✅ Obtenidos " . count($fixtures['matches']) . " partidos\n\n";
    
    echo "📊 PROCESANDO FIXTURES:\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $created = 0;
    $updated = 0;
    
    foreach ($fixtures['matches'] as $fixture) {
        $home_name = $fixture['home_team'];
        $away_name = $fixture['away_team'];
        $date = Carbon::parse($fixture['date']);
        
        // Crear o obtener equipos
        $home_team = Team::firstOrCreate(
            ['name' => $home_name],
            [
                'external_id' => md5($home_name),
                'type' => 'club',
                'short_name' => substr($home_name, 0, 3),
                'country' => 'Spain',
            ]
        );
        
        $away_team = Team::firstOrCreate(
            ['name' => $away_name],
            [
                'external_id' => md5($away_name),
                'type' => 'club',
                'short_name' => substr($away_name, 0, 3),
                'country' => 'Spain',
            ]
        );
        
        // Buscar partido existente (en el mismo día)
        $existing = FootballMatch::where('home_team_id', $home_team->id)
            ->where('away_team_id', $away_team->id)
            ->whereDate('date', $date->toDateString())
            ->first();
        
        if ($existing) {
            // Actualizar
            $existing->update([
                'home_team' => $home_name,
                'away_team' => $away_name,
                'date' => $date,
                'league' => $fixture['league'] ?? 'La Liga',
                'stadium' => $fixture['stadium'] ?? null,
                'status' => 'scheduled',
            ]);
            $updated++;
        } else {
            // Crear
            FootballMatch::create([
                'home_team_id' => $home_team->id,
                'away_team_id' => $away_team->id,
                'home_team' => $home_name,
                'away_team' => $away_name,
                'date' => $date,
                'league' => $fixture['league'] ?? 'La Liga',
                'matchday' => $fixture['matchday'] ?? null,
                'stadium' => $fixture['stadium'] ?? null,
                'status' => 'scheduled',
            ]);
            $created++;
        }
        
        echo "✓ " . $home_name . " vs " . $away_name . " (" . $date->format('d/m/Y H:i') . ")\n";
    }
    
    echo "\n";
    echo "════════════════════════════════════════════════════════════════\n";
    echo "📈 RESUMEN:\n";
    echo "════════════════════════════════════════════════════════════════\n";
    echo "Partidos creados: " . $created . "\n";
    echo "Partidos actualizados: " . $updated . "\n";
    echo "Total procesado: " . ($created + $updated) . "\n\n";
    
    // Verificar en BD
    echo "🔍 VERIFICANDO EN BASE DE DATOS:\n";
    echo "─────────────────────────────────────────────────────────────\n\n";
    
    $matches = DB::table('football_matches')
        ->whereBetween('date', ['2026-01-09 00:00:00', '2026-01-12 23:59:59'])
        ->orderBy('date')
        ->get();
    
    foreach ($matches as $match) {
        $date = Carbon::parse($match->date);
        echo "• " . $match->home_team . " vs " . $match->away_team;
        echo " - " . $date->format('d/m/Y H:i') . "\n";
    }
    
    echo "\n✅ IMPORTACIÓN COMPLETADA EXITOSAMENTE\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
