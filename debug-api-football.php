<?php
$apiKey = getenv('FOOTBALL_API_KEY');
$date = '2026-01-20';

echo "í´ Buscando fixtures en API Football para: $date\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://v3.football.api-sports.io/fixtures?date=$date");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-apisports-key: $apiKey"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n\n";
$data = json_decode($response, true);

if ($httpCode === 200 && $data['response']) {
    echo "Encontrados " . count($data['response']) . " fixtures:\n\n";
    foreach ($data['response'] as $fixture) {
        $id = $fixture['id'];
        $home = $fixture['teams']['home']['name'];
        $away = $fixture['teams']['away']['name'];
        $status = $fixture['fixture']['status'];
        echo "- Fixture $id: $home vs $away (Status: $status)\n";
    }
} else {
    echo "Error o sin resultados\n";
    echo json_encode($data, JSON_PRETTY_PRINT);
}
