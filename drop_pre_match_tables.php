<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    DB::statement('DROP TABLE IF EXISTS group_penalties');
    DB::statement('DROP TABLE IF EXISTS pre_match_resolutions');
    DB::statement('DROP TABLE IF EXISTS pre_match_votes');
    DB::statement('DROP TABLE IF EXISTS pre_match_propositions');
    DB::statement('DROP TABLE IF EXISTS pre_matches');
    echo "✅ Tablas eliminadas exitosamente\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
