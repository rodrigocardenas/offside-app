<?php

/**
 * Script para limpiar datos ficticios de "Fallback (random)"
 * Restaura partidos a estado "Not Started" sin scores ficticios
 */

require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FootballMatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "\n=== LIMPIEZA DE DATOS FICTICIOS (Fallback Random) ===\n\n";

// Buscar partidos con "Fallback (random)" o el score específico que reportó el usuario
$fictionalMatches = FootballMatch::where('events', 'LIKE', '%Fallback (random)%')
    ->orWhere('events', 'LIKE', '%4 goles del local, 1 del visitante%')
    ->orWhere('statistics', 'LIKE', '%Fallback (random)%')
    ->get();

echo "Partidos con datos ficticios encontrados: " . $fictionalMatches->count() . "\n\n";

if ($fictionalMatches->count() === 0) {
    echo "✅ No se encontraron datos ficticios. BD está limpia.\n\n";
    exit(0);
}

// Mostrar partidos a limpiar
foreach ($fictionalMatches as $match) {
    $stats = $match->statistics ? json_decode($match->statistics, true) : [];
    echo "┌─ ID: {$match->id}\n";
    echo "├─ Partido: {$match->home_team} vs {$match->away_team}\n";
    echo "├─ Score: {$match->score}\n";
    echo "├─ Estado: {$match->status}\n";
    echo "├─ Evento: " . substr($match->events ?? '', 0, 80) . "...\n";
    echo "├─ Fuente: " . ($stats['source'] ?? 'Desconocida') . "\n";
    echo "└─ Fecha: {$match->date}\n\n";
}

// Preguntar confirmación
$response = readline("¿Deseas limpiar estos " . $fictionalMatches->count() . " partidos? (s/n): ");

if (strtolower($response) !== 's' && $response !== 'yes') {
    echo "\n❌ Operación cancelada.\n";
    exit(0);
}

// Limpiar datos
$cleanedCount = 0;
foreach ($fictionalMatches as $match) {
    $originalScore = $match->score;

    $match->update([
        'status' => 'Not Started',
        'home_team_score' => null,
        'away_team_score' => null,
        'score' => null,
        'events' => null,
        'statistics' => json_encode([
            'source' => 'CLEANED_FICTIONAL_DATA',
            'cleaned_at' => now()->toIso8601String(),
            'original_fictional_score' => $originalScore,
            'original_status' => 'Match Finished (Fallback)',
            'verified' => false,
            'policy' => 'VERIFIED_ONLY - No fake data allowed',
            'reason' => 'Fallback random scores removed - data not from verified sources'
        ])
    ]);

    $cleanedCount++;
    Log::warning("Datos ficticios limpiados para partido {$match->id}", [
        'home_team' => $match->home_team,
        'away_team' => $match->away_team,
        'original_score' => $originalScore
    ]);

    echo "✓ ID {$match->id}: {$match->home_team} vs {$match->away_team} - Limpiado\n";
}

echo "\n✅ ¡LIMPIEZA COMPLETADA!\n";
echo "   Partidos limpios: {$cleanedCount}\n";
echo "   Todos los scores ficticios han sido removidos\n";
echo "   Los partidos están ahora en estado 'Not Started'\n";
echo "\n✅ Política VERIFIED_ONLY confirmada\n";
echo "   - Solo se aceptan resultados de API Football o Gemini\n";
echo "   - NO se generarán más datos aleatorios\n\n";
