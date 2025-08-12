<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FootballService;
use App\Models\FootballMatch;

class TestMatchUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches:test-update {match_id : ID del partido en la base de datos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba la actualización de un partido específico de la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $matchId = $this->argument('match_id');

        $match = FootballMatch::find($matchId);
        if (!$match) {
            $this->error("Partido con ID $matchId no encontrado en la base de datos");
            return;
        }

        $this->info("Probando actualización para partido:");
        $this->info("ID: {$match->id}");
        $this->info("Equipos: {$match->home_team} vs {$match->away_team}");
        $this->info("Fecha: {$match->date}");
        $this->info("External ID: {$match->external_id}");
        $this->info("Status actual: {$match->status}");
        $this->info("Score actual: {$match->score}");

        $footballService = new FootballService();

        // Probar la actualización
        $updatedMatch = $footballService->updateMatchFromApi($match->id);

        if ($updatedMatch) {
            $this->info("✅ Partido actualizado exitosamente:");
            $this->info("Status nuevo: {$updatedMatch->status}");
            $this->info("Score nuevo: {$updatedMatch->score}");
            $this->info("Events: {$updatedMatch->events}");
            $this->info("Statistics: {$updatedMatch->statistics}");
        } else {
            $this->error("❌ No se pudo actualizar el partido");
        }
    }
}
