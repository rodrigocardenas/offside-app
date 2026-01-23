<?php
require 'vendor/autoload.php';
$app = require_once('bootstrap/app.php');
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║        ✅ VERIFICACIÓN FINAL - EVENTOS Y ESTADÍSTICAS COMPLETAS           ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

$matches = DB::table('football_matches')
    ->whereBetween('date', ['2026-01-20 00:00:00', '2026-01-21 23:59:59'])
    ->orderBy('date')
    ->get();

echo "Total de partidos: " . count($matches) . "\n\n";

$fullData = 0;

foreach ($matches as $m) {
    $events = json_decode($m->events, true);
    $stats = json_decode($m->statistics, true);
    
    $hasEvents = is_array($events) && count($events) > 0;
    $hasStats = is_array($stats) && count($stats) > 0;
    $hasScore = $m->home_team_score !== null;
    
    $status = ($hasEvents && $hasStats && $hasScore) ? '✅' : '⚠️';
    
    printf("%s %-32s %2d-%-2d %-32s\n",
        $status,
        substr($m->home_team, 0, 30),
        $m->home_team_score ?? 0,
        $m->away_team_score ?? 0,
        substr($m->away_team, 0, 30)
    );
    
    if ($hasEvents && $hasStats && $hasScore) {
        $fullData++;
    }
}

echo "\n╔════════════════════════════════════════════════════════════════════════════╗\n";
printf("║ Partidos con datos completos: %-50d    ║\n", $fullData);
printf("║ Porcentaje: %-66.1f%%         ║\n", ($fullData/count($matches))*100);
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

// Mostrar ejemplos
echo "=== EJEMPLOS DE DATOS POBLA DOS ===\n\n";

$examples = [
    551962, // Sporting vs PSG
    551929, // Real Madrid vs Monaco
    551996  // Kairat vs Brugge
];

foreach ($examples as $externalId) {
    $match = DB::table('football_matches')->where('external_id', $externalId)->first();
    if ($match) {
        echo "📍 {$match->home_team} {$match->home_team_score}-{$match->away_team_score} {$match->away_team}\n";
        
        $events = json_decode($match->events, true);
        echo "   Eventos (" . count($events) . "):\n";
        foreach (array_slice($events, 0, 3) as $e) {
            echo "     • {$e['minute']}' {$e['type']}: {$e['player']} ({$e['team']})\n";
        }
        if (count($events) > 3) {
            echo "     • ... y " . (count($events) - 3) . " más\n";
        }
        
        $stats = json_decode($match->statistics, true);
        echo "   Stats:\n";
        echo "     • Source: {$stats['source']}\n";
        echo "     • Total eventos: {$stats['detailed_event_count']}\n";
        echo "     • Possession: {$stats['home_possession']}% vs {$stats['away_possession']}%\n";
        echo "\n";
    }
}
