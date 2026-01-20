<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$apiKey = env('FOOTBALL_DATA_API_KEY');
$competitions = ['PD', 'PL', 'CL', 'SA'];
$allTeams = [];

echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

foreach ($competitions as $code) {
    echo "Obteniendo competencia: $code\n";
    
    $response = Http::withoutVerifying()
        ->withHeaders(['X-Auth-Token' => $apiKey])
        ->get("https://api.football-data.org/v4/competitions/{$code}/matches", [
            'status' => 'SCHEDULED,LIVE,FINISHED',
            'dateFrom' => now()->format('Y-m-d'),
            'dateTo' => now()->addDays(30)->format('Y-m-d'),
        ]);

    echo "Status: " . $response->status() . "\n";
    
    if ($response->ok()) {
        $data = $response->json();
        $matchCount = count($data['matches'] ?? []);
        echo "Partidos encontrados: $matchCount\n";
        
        foreach ($response->json()['matches'] ?? [] as $match) {
            foreach (['homeTeam', 'awayTeam'] as $key) {
                $team = $match[$key];
                if ($team && !isset($allTeams[$team['name']])) {
                    $allTeams[$team['name']] = $team;
                }
            }
        }
    } else {
        echo "Error: " . $response->body() . "\n";
    }
    echo "\n";
}

ksort($allTeams);

echo "=== LISTA DE NOMBRES DE LA API ===\n\n";
foreach ($allTeams as $name => $team) {
    echo $name . "\n";
}

echo "\n\n=== QUERIES UPDATE ===\n\n";
foreach ($allTeams as $apiName => $team) {
    echo "UPDATE teams SET api_name = '" . addslashes($apiName) . "' WHERE name = '" . addslashes($apiName) . "';\n";
}

echo "\n\n=== RESUMEN ===\n";
echo "Total de equipos en API: " . count($allTeams) . "\n";
