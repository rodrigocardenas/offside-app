<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = getenv('FOOTBALL_API_KEY');
$date = '2026-01-20';

echo "��� Buscando Inter vs Arsenal en Champions League para $date\n";
echo "API Key (primeros 15 chars): " . substr($apiKey, 0, 15) . "...\n\n";

// Paso 1: Obtener todas las competiciones
echo "Paso 1️⃣: Buscando competición de Champions League...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://v3.football.api-sports.io/competitions");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-apisports-key: $apiKey"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$errorMsg = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($errorMsg) echo "Curl Error: $errorMsg\n";

$competitions = json_decode($response, true);
$clId = null;

if ($httpCode === 200 && isset($competitions['response'])) {
    foreach ($competitions['response'] as $comp) {
        if (isset($comp['league']) && stripos($comp['league'], 'UEFA Champions') !== false) {
            $clId = $comp['id'];
            echo "✅ Champions League encontrada con ID: $clId\n\n";
            break;
        }
    }
}

if (!$clId) {
    echo "❌ No se encontró Champions League\n";
    echo "Competiciones disponibles:\n";
    if (isset($competitions['response'])) {
        foreach (array_slice($competitions['response'], 0, 10) as $comp) {
            echo "  - {$comp['league']} (ID: {$comp['id']})\n";
        }
    }
    exit(1);
}

// Paso 2: Obtener fixtures de CL para la fecha
echo "Paso 2️⃣: Buscando fixtures de CL para $date...\n";
$ch = curl_init();
$params = http_build_query([
    'league' => $clId,
    'season' => 2026,
    'date' => $date
]);
curl_setopt($ch, CURLOPT_URL, "https://v3.football.api-sports.io/fixtures?$params");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-apisports-key: $apiKey"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$fixtures = json_decode($response, true);

if ($httpCode === 200 && isset($fixtures['response'])) {
    echo "✅ Encontrados " . count($fixtures['response']) . " fixtures para esa fecha:\n\n";
    
    $interArsenalFixture = null;
    foreach ($fixtures['response'] as $fixture) {
        $id = $fixture['fixture']['id'];
        $home = $fixture['teams']['home']['name'];
        $away = $fixture['teams']['away']['name'];
        $status = $fixture['fixture']['status'];
        $homeGoals = $fixture['goals']['home'];
        $awayGoals = $fixture['goals']['away'];
        
        echo "ID: $id | $home ($homeGoals) vs ($awayGoals) $away | Status: $status\n";
        
        // Buscar Inter vs Arsenal
        if ((stripos($home, 'Inter') !== false || stripos($home, 'Internazionale') !== false) &&
            stripos($away, 'Arsenal') !== false) {
            $interArsenalFixture = $fixture;
            echo "  ⭐⭐⭐ ESTE ES INTER VS ARSENAL\n";
        }
        // También buscar al revés (si estuviera invertido)
        elseif (stripos($home, 'Arsenal') !== false &&
                (stripos($away, 'Inter') !== false || stripos($away, 'Internazionale') !== false)) {
            $interArsenalFixture = $fixture;
            echo "  ⭐⭐⭐ ESTE ES ARSENAL VS INTER (invertido)\n";
        }
    }
    
    if ($interArsenalFixture) {
        echo "\n" . str_repeat("=", 70) . "\n";
        echo "��� INTER VS ARSENAL ENCONTRADO:\n";
        echo "Fixture ID (API Football): " . $interArsenalFixture['fixture']['id'] . "\n";
        echo "Home: " . $interArsenalFixture['teams']['home']['name'] . "\n";
        echo "Away: " . $interArsenalFixture['teams']['away']['name'] . "\n";
        echo "Score: " . $interArsenalFixture['goals']['home'] . " - " . $interArsenalFixture['goals']['away'] . "\n";
        echo "Status: " . $interArsenalFixture['fixture']['status'] . "\n";
        echo "Fecha: " . $interArsenalFixture['fixture']['date'] . "\n";
        echo str_repeat("=", 70) . "\n";
    } else {
        echo "\n❌ Inter vs Arsenal NO encontrado en los fixtures de esa fecha\n";
        echo "¿Quizás es en otra fecha?\n";
    }
} else {
    echo "❌ Error obteniendo fixtures\n";
    print_r($fixtures);
}
