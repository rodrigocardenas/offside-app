<?php
/**
 * Script para verificar y limpiar datos ficticios generados en producción
 * Busca partidos con "Fallback (random)" o resultados inconsistentes
 */

require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FootballMatch;

echo "\n=== VERIFICACIÓN DE DATOS FICTICIOS EN PRODUCCIÓN ===\n\n";

// Buscar partidos con "Fallback (random)" en statistics
$problematicMatches = FootballMatch::whereRaw("JSON_EXTRACT(statistics, '$.source') LIKE '%Fallback%'")
    ->orWhereRaw("JSON_EXTRACT(statistics, '$.verified') = false AND JSON_EXTRACT(statistics, '$.source') NOT LIKE '%NO_ENCONTRADO%'")
    ->get();

echo "Partidos con datos potencialmente ficticios encontrados: " . $problematicMatches->count() . "\n\n";

foreach ($problematicMatches as $match) {
    $stats = json_decode($match->statistics, true) ?? [];
    echo "ID: {$match->id}\n";
    echo "  Equipo: {$match->home_team} vs {$match->away_team}\n";
    echo "  Resultado: {$match->score} (Home: {$match->home_team_score}, Away: {$match->away_team_score})\n";
    echo "  Estado: {$match->status}\n";
    echo "  Fuente: " . ($stats['source'] ?? 'Desconocida') . "\n";
    echo "  Verificado: " . ($stats['verified'] ? 'Sí' : 'No') . "\n";
    echo "  Evento: " . ($match->events ?? 'N/A') . "\n";
    echo "\n";
}

if ($problematicMatches->count() > 0) {
    echo "\n⚠️ ENCONTRADOS " . $problematicMatches->count() . " PARTIDOS CON DATA FICTICIA\n";
    echo "\nPara limpiar, ejecuta:\n";
    echo "php artisan command:cleanup-fictional-data\n";
} else {
    echo "\n✅ No se encontraron datos ficticios\n";
}
