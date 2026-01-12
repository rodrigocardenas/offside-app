<?php

/**
 * SCRIPT SETUP: Poblador de Competiciones
 *
 * Carga las competiciones principales en la BD
 * Ejecutar antes de usar test-complete-cycle.php
 *
 * Uso: php scripts/setup-competitions.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Competition;

// Colores
class Colors {
    const RESET = "\033[0m";
    const GREEN = "\033[32m";
    const BLUE = "\033[34m";
    const CYAN = "\033[36m";
}

function print_section($title) {
    echo "\n" . Colors::CYAN . "=== " . $title . " ===" . Colors::RESET . "\n";
}

function print_success($message) {
    echo Colors::GREEN . "✓ " . $message . Colors::RESET . "\n";
}

function print_info($message) {
    echo Colors::BLUE . "ℹ " . $message . Colors::RESET . "\n";
}

// ============================================================================
// COMPETICIONES A CREAR
// ============================================================================

print_section("SETUP: Poblador de Competiciones");

$competitions = [
    [
        'name' => 'La Liga',
        'type' => 'laliga',
        'code' => 'LaLiga EA Sports',
        'external_id' => 2014,
        'season' => 2023,
        'emblem' => 'https://crests.football-data.org/760.png',
    ],
    [
        'name' => 'Premier League',
        'type' => 'premier',
        'code' => 'Premier League',
        'external_id' => 2021,
        'season' => 2023,
        'emblem' => 'https://crests.football-data.org/398.png',
    ],
    [
        'name' => 'UEFA Champions League',
        'type' => 'champions',
        'code' => 'UEFA Champions League',
        'external_id' => 2001,
        'season' => 2023,
        'emblem' => 'https://crests.football-data.org/679.png',
    ],
    [
        'name' => 'Bundesliga',
        'type' => 'bundesliga',
        'code' => 'Bundesliga',
        'external_id' => 2002,
        'season' => 2023,
        'emblem' => 'https://crests.football-data.org/694.png',
    ],
    [
        'name' => 'Serie A',
        'type' => 'serie_a',
        'code' => 'Serie A',
        'external_id' => 2019,
        'season' => 2023,
        'emblem' => 'https://crests.football-data.org/796.png',
    ],
    [
        'name' => 'Ligue 1',
        'type' => 'ligue1',
        'code' => 'Ligue 1',
        'external_id' => 2015,
        'season' => 2023,
        'emblem' => 'https://crests.football-data.org/762.png',
    ],
];

$createdCount = 0;
$existingCount = 0;

foreach ($competitions as $compData) {
    try {
        $competition = Competition::updateOrCreate(
            ['type' => $compData['type']],
            $compData
        );

        if ($competition->wasRecentlyCreated) {
            print_success("Competición creada: {$compData['name']}");
            $createdCount++;
        } else {
            print_info("Competición existente: {$compData['name']}");
            $existingCount++;
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

print_section("Resumen");
print_info("Competiciones creadas: {$createdCount}");
print_info("Competiciones existentes: {$existingCount}");
print_success("Total de competiciones en BD: " . Competition::count());

print_info("Ahora puedes ejecutar: php scripts/test-complete-cycle.php");
echo "\n";
