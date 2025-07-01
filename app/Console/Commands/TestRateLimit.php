<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FootballService;
use App\Models\FootballMatch;

class TestRateLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:rate-limit {match_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test rate limiting handling';

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

        $this->info("Probando rate limiting con partido ID: {$match->id}");
        $this->info("Equipos: {$match->home_team} vs {$match->away_team}");
        $this->info("Liga: {$match->league}");

        $startTime = microtime(true);

        try {
            $updatedMatch = $footballService->updateMatchFromApi($match->id);

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            if ($updatedMatch) {
                $this->info("✅ Partido actualizado exitosamente en {$duration} segundos");
                $this->info("Nuevo estado: {$updatedMatch->status}");
                $this->info("Score: {$updatedMatch->score}");
            } else {
                $this->error("❌ No se pudo actualizar el partido después de {$duration} segundos");
            }
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->error("❌ Error después de {$duration} segundos: " . $e->getMessage());
        }
    }
}
