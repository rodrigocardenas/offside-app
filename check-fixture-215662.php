<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = getenv('FOOTBALL_API_KEY');

echo "Ì¥ç Verificando qu√© partido es el fixture 215662\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://v3.football.api-sports.io/fixtures?id=215662");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-apisports-key: $apiKey"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$data = json_decode($response, true);

if ($httpCode === 200 && isset($data['response']) && count($data['response']) > 0) {
    $fixture = $data['response'][0];
    echo "‚úÖ Fixture encontrado:\n";
    echo "ID: {$fixture['fixture']['id']}\n";
    echo "Fecha: {$fixture['fixture']['date']}\n";
    echo "Home: {$fixture['teams']['home']['name']}\n";
    echo "Away: {$fixture['teams']['away']['name']}\n";
    echo "Score: {$fixture['goals']['home']} - {$fixture['goals']['away']}\n";
    echo "Liga: {$fixture['league']['name']}\n";
    echo "Status: {$fixture['fixture']['status']}\n";
} else {
    echo "‚ùå Error o fixture no encontrado\n";
    print_r($data);
}
