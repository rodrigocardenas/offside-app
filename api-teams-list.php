<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$apiKey = env('FOOTBALL_DATA_API_KEY');
$allTeams = [];

// Definir las competencias
$competitions = [
    'PD' => 'La Liga',
    'PL' => 'Premier League',
    'CL' => 'Champions League',
    'SA' => 'Serie A'
];

echo "\n=== OBTENIENDO EQUIPOS DE LA API ===\n\n";

foreach ($competitions as $code => $name) {
    echo "[INFO] Obteniendo equipos de $name ($code)...\n";

    try {
        $response = Http::withoutVerifying()
            ->timeout(10)
            ->withHeaders(['X-Auth-Token' => $apiKey])
            ->get("https://api.football-data.org/v4/competitions/{$code}/matches", [
                'status' => 'SCHEDULED,LIVE,FINISHED',
                'dateFrom' => now()->format('Y-m-d'),
                'dateTo' => now()->addDays(30)->format('Y-m-d'),
            ]);

        if (!$response->ok()) {
            echo "[ERROR] Status " . $response->status() . " para $code\n";
            continue;
        }

        $matches = $response->json()['matches'] ?? [];
        echo "[OK] $name: " . count($matches) . " partidos\n";

        foreach ($matches as $match) {
            foreach (['homeTeam', 'awayTeam'] as $key) {
                if (isset($match[$key]['name'])) {
                    $teamName = $match[$key]['name'];
                    if (!isset($allTeams[$teamName])) {
                        $allTeams[$teamName] = true;
                    }
                }
            }
        }
    } catch (\Exception $e) {
        echo "[ERROR] Exception: " . $e->getMessage() . "\n";
    }
}

ksort($allTeams);

echo "\n\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║           LISTA DE NOMBRES DE EQUIPOS (API)               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

foreach ($allTeams as $name => $v) {
    echo $name . "\n";
}

echo "\n\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║              QUERIES UPDATE POR NOMBRE                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

foreach ($allTeams as $apiName => $v) {
    $escaped = addslashes($apiName);
    echo "UPDATE teams SET api_name = '{$escaped}' WHERE name = '{$escaped}';\n";
}

echo "\n\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Total de equipos: " . count($allTeams) . "\n";
echo "═══════════════════════════════════════════════════════════════\n";
