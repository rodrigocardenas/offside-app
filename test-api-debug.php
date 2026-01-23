<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║      DEBUG: Verificar API Football PRO Respuesta             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Obtener fixture 551996 (FK Kairat vs Club Brugge)
$response = \Illuminate\Support\Facades\Http::withoutVerifying()->withHeaders([
    'x-apisports-key' => config('services.football.key'),
])->get('https://v3.football.api-sports.io/fixtures', [
    'id' => 551996
]);

echo "Status: " . $response->status() . "\n";
echo "Headers:\n";
foreach ($response->headers() as $key => $value) {
    if (in_array($key, ['x-ratelimit-requests-limit', 'x-ratelimit-requests-remaining'])) {
        echo "  $key: " . $value[0] . "\n";
    }
}
echo "\n";

$data = $response->json();
if (empty($data['response'])) {
    echo "❌ No hay fixtures\n";
    exit(1);
}

$fixture = $data['response'][0];
echo "Fixture ID: " . $fixture['fixture']['id'] . "\n";
echo "Status: " . $fixture['fixture']['status']['short'] . "\n";
echo "Goals:\n";
echo "  Home: " . var_export($fixture['goals']['home'], true) . "\n";
echo "  Away: " . var_export($fixture['goals']['away'], true) . "\n";

// Mostrar estructura completa de goals
echo "\nEstructura goals:\n";
echo json_encode($fixture['goals'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

// Mostrar estructura completa de teams
echo "\nTeams:\n";
echo "  Home: " . $fixture['teams']['home']['name'] . "\n";
echo "  Away: " . $fixture['teams']['away']['name'] . "\n";

?>
