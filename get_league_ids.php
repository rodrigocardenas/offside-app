<?php
require 'vendor/autoload.php';

// Leer .env manualmente
if (file_exists('.env')) {
    $lines = file('.env');
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line && !str_starts_with($line, '#')) {
            [$key, $value] = array_pad(explode('=', $line, 2), 2, null);
            putenv("{$key}={$value}");
        }
    }
}

$apiKey = getenv('FOOTBALL_API_KEY') ?: getenv('FOOTBALL_DATA_API_TOKEN') ?: 'your_key_here';

// Competencias internacionales a buscar
$toSearch = [
    'World Cup',
    'Copa America',
    'EURO',
    'Africa Cup',
    'Asian Cup',
    'Gold Cup',
    'Champions League',
    'Premier League',
    'La Liga',
    'Serie A',
    'Ligue 1',
    'Bundesliga',
];

echo "Buscando competencias en api-sports.io...\n\n";

try {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://v3.football.api-sports.io/leagues?season=2025',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['x-apisports-key: ' . $apiKey],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo "Error: " . curl_error($ch) . "\n";
        exit(1);
    }
    curl_close($ch);

    $data = json_decode($response, true);
    $leagues = $data['response'] ?? [];

    echo "Total de ligas: " . count($leagues) . "\n\n";
    echo "=== COMPETENCIAS INTERNACIONALES ===\n\n";

    $found = [];
    foreach ($leagues as $league) {
        $id = $league['league']['id'] ?? null;
        $name = $league['league']['name'] ?? '';
        $country = $league['country']['name'] ?? '';
        
        // Buscar coincidencias
        foreach ($toSearch as $search) {
            if (stripos($name, $search) !== false) {
                if (!isset($found[$id])) {  // Evitar duplicados
                    echo "'{$name}' => {$id},  // {$country}\n";
                    $found[$id] = true;
                }
                break;
            }
        }
    }
    
    // Buscar específicamente Copa América y EURO
    echo "\n\n=== BÚSQUEDA ADICIONAL ===\n\n";
    foreach ($leagues as $league) {
        $id = $league['league']['id'] ?? null;
        $name = $league['league']['name'] ?? '';
        $country = $league['country']['name'] ?? '';
        
        if (
            (stripos($name, 'Copa') !== false && stripos($name, 'America') !== false && stripos($name, 'Qualification') === false && stripos($name, 'Femenina') === false) ||
            (stripos($name, 'EURO') !== false && stripos($name, 'Qualification') === false && stripos($name, 'U21') === false && stripos($name, 'Women') === false && stripos($name, 'U19') === false && stripos($name, 'U17') === false)
        ) {
            if (!isset($found[$id])) {
                echo "'{$name}' => {$id},  // {$country}\n";
                $found[$id] = true;
            }
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
