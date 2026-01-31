<?php

namespace App\Console\Commands;

use App\Models\FootballMatch;
use App\Services\FootballService;
use Illuminate\Console\Command;

class DiagnoseStatistics extends Command
{
    protected $signature = 'app:diagnose-statistics {--date=2026-01-28}';
    protected $description = 'Diagnose which matches are missing statistics';

    public function handle()
    {
        $date = $this->option('date');
        $matches = FootballMatch::where('date', 'LIKE', "$date%")
            ->orderBy('id')
            ->get();

        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("📊 DIAGNOSTICANDO ESTADÍSTICAS - $date");
        $this->info("═══════════════════════════════════════════════════════════\n");

        $footballService = app(FootballService::class);
        $withStats = 0;
        $withoutStats = 0;

        foreach ($matches as $match) {
            $stats = json_decode($match->statistics, true);
            $hasStats = $stats && isset($stats['teams']) && count($stats['teams']) > 0;
            $statsSize = strlen($match->statistics);

            if ($hasStats) {
                $withStats++;
                $this->line("✅ ID {$match->id}: {$match->home_team} vs {$match->away_team}");
                $this->line("   Stats: {$statsSize} bytes | Teams: " . count($stats['teams']));
            } else {
                $withoutStats++;
                $this->line("❌ ID {$match->id}: {$match->home_team} vs {$match->away_team}");
                $this->line("   Stats: {$statsSize} bytes | External ID: {$match->external_id}");

                // Intentar obtener estadísticas manualmente
                if ($match->external_id && is_numeric($match->external_id)) {
                    $this->line("   → Intentando obtener desde API...");
                    $apiStats = $footballService->obtenerEstadisticasFixture($match->external_id);
                    if ($apiStats) {
                        $this->line("   ✓ API retorna datos: " . json_encode($apiStats));
                    } else {
                        $this->line("   ✗ API retorna NULL");
                    }
                }
            }
            $this->newLine();
        }

        $this->info("━━━ RESUMEN ━━━");
        $this->line("Con estadísticas: $withStats");
        $this->line("Sin estadísticas: $withoutStats");
        $this->line("Total: " . $matches->count());
    }
}
