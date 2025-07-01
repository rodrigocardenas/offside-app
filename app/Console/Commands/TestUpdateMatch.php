<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FootballService;
use App\Models\FootballMatch;

class TestUpdateMatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:update-match {match_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test updateMatchFromApi functionality';

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

        $this->info("Probando actualización del partido ID: {$match->id}");
        $this->info("Equipos: {$match->home_team} vs {$match->away_team}");
        $this->info("Liga: {$match->league}");
        $this->info("Fecha: {$match->date}");
        $this->info("Estado actual: {$match->status}");

        try {
            $updatedMatch = $footballService->updateMatchFromApi($match->id);

            if ($updatedMatch) {
                $this->info("✅ Partido actualizado exitosamente");
                $this->info("Nuevo estado: {$updatedMatch->status}");
                $this->info("Score: {$updatedMatch->score}");
                $this->info("Events: {$updatedMatch->events}");
            } else {
                $this->error("❌ No se pudo actualizar el partido");
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
        }
    }
}
