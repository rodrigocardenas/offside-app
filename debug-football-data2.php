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
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($curlError) echo "Curl Error: $curlError\n";

$data = json_decode($response, true);

if ($httpCode === 200 && isset($data['match'])) {
    $match = $data['match'];
    echo "‚úÖ Fixture encontrado:\n";
    echo "Home: {$match['homeTeam']['name']}\n";
    echo "Away: {$match['awayTeam']['name']}\n";
    echo "Score: {$match['score']['fullTime']['home']} - {$match['score']['fullTime']['away']}\n";
    
    echo "\nÌ≥ç Goles:\n";
    if (isset($match['goals']) && is_array($match['goals'])) {
        echo "Encontrados " . count($match['goals']) . " goles:\n";
        foreach ($match['goals'] as $goal) {
            echo "  - Min {$goal['minute']}: {$goal['scorer']} ({$goal['team']['name']})\n";
        }
    } else {
        echo "NO goles disponibles en key 'goals'\n";
    }
} else {
    echo "Error: HTTP $httpCode\n";
    if ($data === null) {
        echo "Response es NULL: " . substr($response, 0, 200) . "\n";
    } else {
        print_r($data);
    }
}
