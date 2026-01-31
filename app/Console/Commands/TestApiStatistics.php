<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestApiStatistics extends Command
{
    protected $signature = 'app:test-api-stats {fixtureId}';
    protected $description = 'Probar endpoint de estadísticas de API Football';

    public function handle()
    {
        $fixtureId = $this->argument('fixtureId');
        $apiKey = config('services.football.key') ?? env('FOOTBALL_API_KEY');

        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("🧪 PROBANDO ENDPOINT /fixtures/statistics");
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("Fixture ID: {$fixtureId}");
        $this->info("API Key: " . (substr($apiKey, 0, 5) . "***"));

        $this->info("\n➡️  Llamando a /fixtures/statistics?fixture={$fixtureId}\n");

        try {
            $response = Http::withoutVerifying()
                ->withHeaders(['x-apisports-key' => $apiKey])
                ->timeout(10)
                ->get("https://v3.football.api-sports.io/fixtures/statistics", [
                    'fixture' => $fixtureId
                ]);

            $this->line("Status: " . $response->status());
            $this->line("Headers: " . json_encode($response->headers()));

            $data = $response->json();

            $this->info("\n━━━ RESPUESTA COMPLETA ━━━");
            $this->line(json_encode($data, JSON_PRETTY_PRINT));

            if ($response->successful()) {
                $stats = $data['response'] ?? [];
                $this->info("\n━━━ ANÁLISIS ━━━");
                $this->line("Estadísticas por equipo: " . count($stats));

                foreach ($stats as $idx => $teamStats) {
                    $teamName = $teamStats['team']['name'] ?? 'Unknown';
                    $statsArray = $teamStats['statistics'] ?? [];
                    $this->line("\n$teamName: " . count($statsArray) . " estadísticas");

                    foreach ($statsArray as $stat) {
                        $type = $stat['type'] ?? 'Unknown';
                        $value = $stat['value'] ?? 'N/A';
                        $this->line("  • {$type}: {$value}");
                    }
                }
            } else {
                $this->error("\nError en la API: Status " . $response->status());
            }
        } catch (\Exception $e) {
            $this->error("\n❌ Excepción: " . $e->getMessage());
        }
    }
}
