<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FootballMatch;

echo "📊 ESTADO DE PARTIDOS EN LA BD\n";
echo "================================\n\n";

$total = FootballMatch::count();
echo "Total de partidos: {$total}\n\n";

echo "Partidos por estado:\n";
$byStatus = FootballMatch::select('status', \DB::raw('COUNT(*) as count'))
    ->groupBy('status')->get();
$byStatus->each(function($row) {
    echo "  • {$row->status}: {$row->count}\n";
});

echo "\nÚltimos 5 partidos ordenados por fecha:\n";
FootballMatch::orderBy('date', 'desc')->take(5)->get()->each(function($m) {
    $date = $m->date->format('Y-m-d H:i');
    echo "  • {$date} - {$m->home_team} vs {$m->away_team} ({$m->status})\n";
});

echo "\n🔍 Partidos con fecha >= HOY:\n";
$futureCount = FootballMatch::where('date', '>=', now())->count();
echo "  Encontrados: {$futureCount}\n";

echo "\n🔍 Partidos con status 'Not Started' y fecha >= HOY:\n";
$futureNotStarted = FootballMatch::where('status', 'Not Started')
    ->where('date', '>=', now())
    ->count();
echo "  Encontrados: {$futureNotStarted}\n";

if ($futureNotStarted === 0) {
    echo "\n❌ PROBLEMA IDENTIFICADO:\n";
    echo "   No hay partidos próximos en la BD.\n";
    echo "   Las preguntas NO se pueden crear sin partidos.\n\n";
    echo "   SOLUCIONES:\n";
    echo "   1. Actualizar fixtures desde la API:\n";
    echo "      php artisan app:update-fixtures-nightly\n\n";
    echo "   2. O importar partidos manualmente:\n";
    echo "      php artisan gemini:fetch-fixtures premier --force\n\n";
    echo "   3. O crear partidos de prueba:\n";
    echo "      php artisan seed --class=FootballMatchSeeder\n";
}
