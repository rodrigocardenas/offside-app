<?php

use Illuminate\Support\Facades\Route;
use App\Models\Match;

Route::get('/debug/matches', function () {
    $now = now();

    // Check direct database
    $matches = Match::where('kick_off_time', '>=', $now)
        ->orderBy('kick_off_time')
        ->limit(10)
        ->with(['homeTeam', 'awayTeam', 'competition'])
        ->get();

    echo "═══════════════════════════════════════════════════════════\n";
    echo "DEBUG: Partidos en Base de Datos\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "Actual time: " . $now->toDateTimeString() . "\n";
    echo "Total matches (próximos): " . $matches->count() . "\n\n";

    if ($matches->isEmpty()) {
        echo "❌ No hay partidos próximos\n";

        $all = Match::limit(5)->get();
        echo "\nTodos los partidos en BD (limit 5):\n";
        $all->each(function($m) {
            echo "- ID: {$m->id}, Kick-off: {$m->kick_off_time}\n";
        });
    } else {
        $matches->each(function($m) {
            echo "Match #{$m->id}:\n";
            echo "  Kick-off: {$m->kick_off_time}\n";
            echo "  Home: " . ($m->homeTeam?->name ?? 'NULL') . "\n";
            echo "  Away: " . ($m->awayTeam?->name ?? 'NULL') . "\n";
            echo "  Competition: " . ($m->competition?->name ?? 'NULL') . "\n\n";
        });
    }

    echo "═══════════════════════════════════════════════════════════\n";
});
