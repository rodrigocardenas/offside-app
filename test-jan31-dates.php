<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FootballMatch;
use App\Models\Question;

echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║   TEST: Verificación de Comando para partidos Jan 31, 2026      ║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n\n";

// Get matches from Jan 31
$targetDate = '2026-01-31';
$allMatches = FootballMatch::whereDate('date', $targetDate)->get();

echo "📅 Buscando matches del 31 de Enero de 2026\n\n";

if ($allMatches->count() == 0) {
    echo "❌ No hay matches exactamente el 31 de Enero\n\n";
    echo "✅ Opción 1: Usar un rango de fechas diferente\n";
    echo "   Disponible matches para:\n";
    
    $available = FootballMatch::selectRaw('DATE(date) as match_date')
        ->distinct()
        ->orderBy('match_date', 'desc')
        ->limit(5)
        ->get();
    
    foreach($available as $m) {
        $count = FootballMatch::whereDate('date', $m->match_date)->count();
        echo "   - {$m->match_date} ({$count} matches)\n";
    }
} else {
    echo "✅ Encontrados " . $allMatches->count() . " matches en $targetDate\n\n";
    
    // Show matches with status
    $allMatches->each(function($m) {
        $qCount = $m->questions()->count();
        echo "  Match {$m->id}: {$m->home_team} vs {$m->away_team}\n";
        echo "    Status: {$m->status} | Questions: {$qCount}\n";
    });
}

echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "📋 CÓMO USAR EL COMANDO:\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "Para verificar estos matches:\n";
echo "  php artisan app:force-verify-questions --days=2 --limit=50\n\n";

echo "Para RE-verificar y reasignar puntos:\n";
echo "  php artisan app:force-verify-questions --days=2 --limit=50 --re-verify\n\n";

echo "Para ver primero qué se hará (sin ejecutar):\n";
echo "  php artisan app:force-verify-questions --days=2 --limit=50 --dry-run\n\n";

echo "═══════════════════════════════════════════════════════════════════\n";
echo "✨ DESPUÉS DE EJECUTAR:\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "Los puntos se calculan dinámicamente en estas vistas:\n";
echo "  - Dashboard (/dashboard)\n";
echo "  - Rankings (/rankings)\n";
echo "  - Grupo (/groups/{id})\n\n";

echo "Los puntos se almacenan en:\n";
echo "  - answers.points_earned (columna)\n";
echo "  - Se suman automáticamente por usuario\n\n";
