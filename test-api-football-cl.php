<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = getenv('FOOTBALL_API_KEY');
$date = '2026-01-20';

echo "��� Buscando fixtures de Champions League en API Football para: $date\n";
echo "API Key: " . substr($apiKey, 0, 15) . "...\n\n";

// Paso 1: Buscar competición de Champions League para obtener su ID
echo "Paso 1️⃣: Buscando competición de Champions League...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://v3.football.api-sports.io/competitions");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-apisports-key: $apiKey"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$data = json_decode($response, true);

$clId = null;
if ($httpCode === 200 && isset($data['response'])) {
    foreach ($data['response'] as $comp) {
        if (isset($comp['league']) && strpos($comp['league'], 'UEFA Champions') !== false) {
            $clId = $comp['id'];
            echo "✅ Champions League ID encontrado: $clId\n\n";
            break;
        }
    }
}

if (!$clId) {
    echo "❌ Champions League no encontrada\n";
    exit(1);
}

// Paso 2: Buscar fixtures de CL para esa fecha
echo "Paso 2️⃣: Buscando fixtures de CL para $date...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://v3.football.api-sports.io/fixtures");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-apisports-key: $apiKey"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// Parámetros de query
$params = http_build_query([
    'date' => $date,
    'league' => $clId
]);

curl_setopt($ch, CURLOPT_URL, "https://v3.football.api-sports.io/fixtures?$params");
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$fixtures = json_decode($response, true);

if ($httpCode === 200 && isset($fixtures['response'])) {
    echo "✅ Encontrados " . count($fixtures['response']) . " fixtures:\n\n";
    
    $interArsenalFixture = null;
    foreach ($fixtures['response'] as $fixture) {
        $id = $fixture['fixture']['id'];
        $home = $fixture['teams']['home']['name'];
        $away = $fixture['teams']['away']['name'];
        $status = $fixture['fixture']['status'];
        
        echo "ID: $id | $home vs $away (Status: $status)\n";
        
        if ((strpos($home, 'Inter') !== false || strpos($home, 'Internazionale') !== false) &&
            (strpos($away, 'Arsenal') !== false)) {
            $interArsenalFixture = $fixture;
            echo "  ⭐ ESTE ES INTER VS ARSENAL\n";
        }
    }
    
    if ($interArsenalFixture) {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "��� INTER VS ARSENAL ENCONTRADO:\n";
        echo "Fixture ID (API Football): " . $interArsenalFixture['fixture']['id'] . "\n";
        echo "Score: " . $interArsenalFixture['goals']['home'] . " - " . $interArsenalFixture['goals']['away'] . "\n";
        echo "Status: " . $interArsenalFixture['fixture']['status'] . "\n";
    }
} else {
    echo "❌ Error obteniendo fixtures\n";
    print_r($fixtures);
}
