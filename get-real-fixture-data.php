<?php
$apiKey = getenv('FOOTBALL_DATA_API_KEY');
$fixtureId = '551933'; // External ID de Inter vs Arsenal

echo "Ì¥ç Obteniendo datos reales del fixture: $fixtureId\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.football-data.org/v4/matches/$fixtureId");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Auth-Token: $apiKey"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n\n";
$data = json_decode($response, true);

if ($httpCode === 200 && $data['match']) {
    $match = $data['match'];
    echo "Ì≥ä DATOS DEL FIXTURE:\n";
    echo "ID: {$match['id']}\n";
    echo "Competici√≥n: {$match['competition']['name']}\n";
    echo "Fecha: {$match['utcDate']}\n";
    echo "Status: {$match['status']}\n";
    echo "Home: {$match['homeTeam']['name']}\n";
    echo "Away: {$match['awayTeam']['name']}\n";
    echo "Score (FT): {$match['score']['fullTime']['home']} - {$match['score']['fullTime']['away']}\n";
    
    echo "\nÌ≥ç HAS GOALS: " . (isset($match['goals']) ? 'YES' : 'NO') . "\n";
    if (isset($match['goals']) && count($match['goals']) > 0) {
        echo "Goles encontrados: " . count($match['goals']) . "\n";
        foreach ($match['goals'] as $goal) {
            echo "  - {$goal['minute']}' - {$goal['scorer']['name']} ({$goal['team']['name']})\n";
        }
    }
    
    echo "\nÌ≥ä HAS STATS: " . (isset($match['statistics']) ? 'YES' : 'NO') . "\n";
    if (isset($match['statistics']) && count($match['statistics']) > 0) {
        echo "Estad√≠sticas encontradas\n";
    }
} else {
    echo "Error o sin resultados\n";
    print_r($data);
}
