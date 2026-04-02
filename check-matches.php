<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Match;
use Illuminate\Support\Facades\Log;

// Get matches from database
$matches = Match::where('kick_off_time', '>=', now())
    ->orderBy('kick_off_time')
    ->limit(10)
    ->with(['homeTeam', 'awayTeam', 'competition'])
    ->get();

echo "═══════════════════════════════════════════════════════════\n";
echo "Partidos en Base de Datos (próximos 10):\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "Total: " . $matches->count() . " partidos\n\n";

if ($matches->isEmpty()) {
    echo "❌ No hay partidos próximos en la BD\n";
    echo "\nVerificando todos los partidos...\n";
    $all = Match::limit(5)->get();
    echo "Total en toda la tabla: " . $all->count() . "\n";
    $all->each(function($m) {
        echo "  - Match " . $m->id . ": " . $m->kick_off_time . "\n";
    });
} else {
    echo "✓ Encontrados " . $matches->count() . " partidos:\n";
    $matches->each(function($m) {
        echo "\nMatch ID: " . $m->id . "\n";
        echo "  Hora: " . $m->kick_off_time . "\n";
        echo "  Home: " . ($m->homeTeam ? $m->homeTeam->name : 'N/A') . " (ID:" . $m->home_team_id . ")\n";
        echo "  Away: " . ($m->awayTeam ? $m->awayTeam->name : 'N/A') . " (ID:" . $m->away_team_id . ")\n";
        echo "  Comp: " . ($m->competition ? $m->competition->name : 'N/A') . "\n";
    });
}

echo "\n═══════════════════════════════════════════════════════════\n";
?>
