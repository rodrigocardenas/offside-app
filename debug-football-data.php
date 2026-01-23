<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = getenv('FOOTBALL_DATA_API_KEY');
$fixtureId = '551933';

echo "Ì¥ç Debuggeando Football-Data.org para fixture: $fixtureId\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.football-data.org/v4/matches/$fixtureId");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Auth-Token: $apiKey"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($curlError) echo "Curl Error: $curlError\n";
echo "\nÌ≥¶ Response Raw (primeros 1000 chars):\n";
echo substr($response, 0, 1000) . "\n\n";

$data = json_decode($response, true);

if ($httpCode === 200 && isset($data['match'])) {
    $match = $data['match'];
    echo "‚úÖ Fixture encontrado:\n";
    echo "Home: {$match['homeTeam']['name']}\n";
    echo "Away: {$match['awayTeam']['name']}\n";
    echo "Score: {$match['score']['fullTime']['home']} - {$match['score']['fullTime']['away']}\n";
    
    echo "\nÌ≥ç Goles (goals key):\n";
    if (isset($match['goals']) && is_array($match['goals'])) {
        echo "Encontrados " . count($match['goals']) . " goles:\n";
        foreach ($match['goals'] as $goal) {
            echo "  - {$goal['minute']}' - {$goal['scorer']} ({$goal['team']['name']})\n";
        }
    } else {
        echo "NO hay key 'goals' o est√° vac√≠o\n";
    }
    
    echo "\nÌ≥ä Available keys en match object:\n";
    foreach (array_keys($match) as $key) {
        echo "  - $key\n";
    }
} else {
    echo "Error: HTTP $httpCode\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
}
