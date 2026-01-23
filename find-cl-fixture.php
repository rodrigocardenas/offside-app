<?php
$apiKey = getenv('FOOTBALL_DATA_API_KEY');
$date = '2026-01-20';

echo "í´ Buscando fixtures de Champions League para: $date\n\n";

// Buscar todas las competiciones para obtener el ID de Champions League
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.football-data.org/v4/competitions");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Auth-Token: $apiKey"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code (Competitions): $httpCode\n";
$data = json_decode($response, true);

$clId = null;
foreach ($data['competitions'] as $comp) {
    if (strpos($comp['name'], 'UEFA Champions') !== false) {
        $clId = $comp['id'];
        echo "Champions League ID: $clId\n\n";
        break;
    }
}

if (!$clId) {
    echo "No encontrÃ© Champions League\n";
    exit(1);
}

// Buscar fixtures de CL para esa fecha
echo "í´ Buscando matches de CL para $date\n\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.football-data.org/v4/competitions/$clId/matches?dateFrom=$date&dateTo=$date");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Auth-Token: $apiKey"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code (Matches): $httpCode\n";
$matches = json_decode($response, true);

if ($httpCode === 200 && $matches['matches']) {
    echo "Encontrados " . count($matches['matches']) . " matches:\n\n";
    foreach ($matches['matches'] as $match) {
        $id = $match['id'];
        $home = $match['homeTeam']['name'];
        $away = $match['awayTeam']['name'];
        $status = $match['status'];
        $homeGoals = $match['score']['fullTime']['home'];
        $awayGoals = $match['score']['fullTime']['away'];
        echo "ID: $id\n";
        echo "  $home vs $away\n";
        echo "  Score: $homeGoals - $awayGoals\n";
        echo "  Status: $status\n\n";
    }
} else {
    echo "Error o sin resultados\n";
    print_r($matches);
}
