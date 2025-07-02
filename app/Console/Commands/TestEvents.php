<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FootballService;
use App\Models\FootballMatch;

class TestEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:events {match_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test events and statistics retrieval';

    /**
     * Execute the console command.
     */
    public function handle(FootballService $footballService)
    {
        $matchId = $this->argument('match_id');

        $match = FootballMatch::find($matchId);
        if (!$match) {
            $this->error("Partido con ID $matchId no encontrado");
            return;
        }

        $this->info("Probando obtención de eventos para partido ID: {$match->id}");
        $this->info("Equipos: {$match->home_team} vs {$match->away_team}");
        $this->info("Liga: {$match->league}");

        try {
            // Primero actualizar el partido
            $updatedMatch = $footballService->updateMatchFromApi($match->id);

            if ($updatedMatch) {
                $this->info("\n✅ Partido actualizado exitosamente");
                $this->info("Estado: {$updatedMatch->status}");
                $this->info("Score: {$updatedMatch->score}");

                $this->info("\n📊 EVENTOS:");
                $this->info($updatedMatch->events ?: "No hay eventos registrados");

                $this->info("\n📈 ESTADÍSTICAS:");
                $this->info($updatedMatch->statistics ?: "No hay estadísticas registradas");

            } else {
                $this->error("❌ No se pudo actualizar el partido");
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
        }
    }
}
