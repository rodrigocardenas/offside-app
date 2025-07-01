<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FootballService;
use App\Models\FootballMatch;

class TestFixtureSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:fixture-search {match_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test fixture search functionality';

    /**
     * Execute the console command.
     */
    public function handle(FootballService $footballService)
    {
        $matchId = $this->argument('match_id');

        if ($matchId) {
            $match = FootballMatch::find($matchId);
            if (!$match) {
                $this->error("Partido con ID $matchId no encontrado");
                return;
            }
            $matches = collect([$match]);
        } else {
            // Obtener algunos partidos recientes para probar
            $matches = FootballMatch::where('status', '!=', 'FINISHED')
                ->where('date', '<=', now()->subHours(2))
                ->where('date', '>=', now()->subHours(100))
                ->take(5)
                ->get();
        }

        $this->info("Probando búsqueda de fixtures para " . $matches->count() . " partidos:");

        foreach ($matches as $match) {
            $this->info("\n--- Partido ID: {$match->id} ---");
            $this->info("Equipos: {$match->home_team} vs {$match->away_team}");
            $this->info("Liga: {$match->league}");
            $this->info("Fecha: {$match->date}");
            $this->info("Estado: {$match->status}");

            try {
                $fixtureId = $footballService->buscarFixtureId(
                    $match->league ?? 'champions-league',
                    2025,
                    $match->home_team,
                    $match->away_team
                );

                if ($fixtureId) {
                    $this->info("✅ Fixture ID encontrado: $fixtureId");
                } else {
                    $this->error("❌ No se encontró fixture ID");
                }
            } catch (\Exception $e) {
                $this->error("❌ Error: " . $e->getMessage());
            }
        }
    }
}
