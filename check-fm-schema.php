<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "\n📋 ESTRUCTURA DE TABLA 'football_matches':\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$columns = Schema::getColumns('football_matches');
foreach ($columns as $col) {
    echo "• " . $col['name'] . " (" . $col['type'] . ")\n";
}
