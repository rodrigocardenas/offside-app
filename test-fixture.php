<?php
$apiKey = trim(shell_exec("grep 'FOOTBALL_API_KEY' .env | cut -d'=' -f2"));
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://v3.football.api-sports.io/fixtures?id=215662");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-apisports-key: $apiKey"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if ($data['response']) {
    $match = $data['response'][0];
    echo "Fixture ID: {$match['id']}\n";
    echo "Fecha: {$match['fixture']['date']}\n";
    echo "Home: {$match['teams']['home']['name']}\n";
    echo "Away: {$match['teams']['away']['name']}\n";
    echo "Score: {$match['goals']['home']} - {$match['goals']['away']}\n";
    echo "Status: {$match['fixture']['status']}\n";
}
