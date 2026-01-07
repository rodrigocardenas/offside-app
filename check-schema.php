<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n📋 ESTRUCTURA DE TABLA 'teams':\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$columns = Schema::getColumns('teams');
foreach ($columns as $col) {
    echo "• " . $col['name'] . " (" . $col['type'] . ")\n";
}

echo "\n\n🏆 EQUIPOS EN LA BASE DE DATOS:\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$teams = DB::table('teams')
    ->select('id', 'name', 'external_id')
    ->orderBy('name')
    ->limit(20)
    ->get();

echo "Total de equipos: " . $teams->count() . "\n\n";

foreach ($teams as $team) {
    echo "• [ID: " . str_pad($team->id, 3, '0', STR_PAD_LEFT) . "] " . ($team->name ?: '[SIN NOMBRE]') . "\n";
}
