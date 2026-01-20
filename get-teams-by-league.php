<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$apiKey = env('FOOTBALL_DATA_API_KEY');

$competitions = [
    'PD' => ['name' => 'LA LIGA', 'country' => 'España'],
    'PL' => ['name' => 'PREMIER LEAGUE', 'country' => 'Inglaterra'],
    'CL' => ['name' => 'CHAMPIONS LEAGUE', 'country' => 'Europa'],
    'SA' => ['name' => 'SERIE A', 'country' => 'Italia'],
];

$allTeamsByLeague = [];

foreach ($competitions as $code => $info) {
    echo "[INFO] Obteniendo equipos de {$info['name']}...\n";
    
    $response = Http::withoutVerifying()
        ->timeout(15)
        ->withHeaders(['X-Auth-Token' => $apiKey])
        ->get("https://api.football-data.org/v4/competitions/{$code}/matches", [
            'status' => 'SCHEDULED,LIVE,FINISHED',
            'dateFrom' => now()->format('Y-m-d'),
            'dateTo' => now()->addDays(60)->format('Y-m-d'),
        ]);

    if ($response->ok()) {
        $teams = [];
        foreach ($response->json()['matches'] ?? [] as $match) {
            foreach (['homeTeam', 'awayTeam'] as $key) {
                if (isset($match[$key]['name'])) {
                    $teamName = $match[$key]['name'];
                    if (!isset($teams[$teamName])) {
                        $teams[$teamName] = true;
                    }
                }
            }
        }
        ksort($teams);
        $allTeamsByLeague[$code] = [
            'info' => $info,
            'teams' => array_keys($teams)
        ];
        echo "[OK] {$info['name']}: " . count($teams) . " equipos\n";
    } else {
        echo "[ERROR] Status " . $response->status() . "\n";
    }
}

echo "\n\n";
echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║              EQUIPOS POR LIGA - FOOTBALL-DATA API                         ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

$totalTeams = 0;

foreach ($allTeamsByLeague as $code => $data) {
    $info = $data['info'];
    $teams = $data['teams'];
    
    echo "════════════════════════════════════════════════════════════════════════════\n";
    echo strtoupper("{$info['name']} ({$info['country']})") . " - {$code}\n";
    echo "════════════════════════════════════════════════════════════════════════════\n\n";
    
    foreach ($teams as $team) {
        echo $team . "\n";
    }
    
    echo "\nTotal: " . count($teams) . " equipos\n\n\n";
    $totalTeams += count($teams);
}

echo "════════════════════════════════════════════════════════════════════════════\n";
echo "TOTAL GENERAL: $totalTeams equipos\n";
echo "════════════════════════════════════════════════════════════════════════════\n";
