<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;

class CheckMatch extends Command
{
    protected $signature = 'check:match {match_id}';
    protected $description = 'Check a specific match';

    public function handle()
    {
        $matchId = $this->argument('match_id');

        $match = FootballMatch::find($matchId);

        if (!$match) {
            $this->error("Partido con ID $matchId no encontrado");
            return;
        }

        $this->info("=== INFORMACIÓN DEL PARTIDO ===");
        $this->info("ID: {$match->id}");
        $this->info("Equipos: {$match->home_team} vs {$match->away_team}");
        $this->info("Score: " . ($match->score ?: 'Sin score'));
        $this->info("Estado: {$match->status}");
        $this->info("Fecha: {$match->date}");
        $this->info("Eventos: " . ($match->events ?: 'Sin eventos'));
        $this->info("Estadísticas: " . ($match->statistics ?: 'Sin estadísticas'));
    }
}
