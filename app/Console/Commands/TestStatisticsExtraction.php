<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FootballService;

class TestStatisticsExtraction extends Command
{
    protected $signature = 'app:test-stats-extraction {fixtureId}';
    protected $description = 'Probar extracción de estadísticas';

    public function handle()
    {
        $fixtureId = $this->argument('fixtureId');
        $footballService = app(FootballService::class);

        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("🧪 PROBANDO obtenerEstadisticasFixture()");
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("Fixture ID: {$fixtureId}\n");

        $stats = $footballService->obtenerEstadisticasFixture($fixtureId);

        if ($stats) {
            $this->info("✅ Estadísticas obtenidas\n");
            $this->line(json_encode($stats, JSON_PRETTY_PRINT));
        } else {
            $this->error("❌ obtenerEstadisticasFixture() retornó NULL");
        }
    }
}
