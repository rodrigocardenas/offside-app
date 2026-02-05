<?php

namespace App\Console\Commands;

use App\Models\FootballMatch;
use App\Models\Team;
use Illuminate\Console\Command;

class FillFootballMatchTeamIds extends Command
{
    protected $signature = 'app:fill-match-team-ids';
    protected $description = 'Llena home_team_id y away_team_id en football_matches basándose en nombres de equipos';

    public function handle()
    {
        $this->info('Iniciando llenado de team IDs...');

        // Obtener todos los matches sin home_team_id
        $matches = FootballMatch::whereNull('home_team_id')
            ->orWhereNull('away_team_id')
            ->get();

        $this->info("Procesando {$matches->count()} partidos...");

        $updated = 0;
        $failed = 0;

        foreach ($matches as $match) {
            try {
                // Buscar home team por nombre
                if ($match->home_team && !$match->home_team_id) {
                    $homeTeam = Team::where('name', $match->home_team)
                        ->orWhere('api_name', $match->home_team)
                        ->first();

                    if ($homeTeam) {
                        $match->home_team_id = $homeTeam->id;
                    } else {
                        $this->warn("Home team no encontrado: {$match->home_team}");
                    }
                }

                // Buscar away team por nombre
                if ($match->away_team && !$match->away_team_id) {
                    $awayTeam = Team::where('name', $match->away_team)
                        ->orWhere('api_name', $match->away_team)
                        ->first();

                    if ($awayTeam) {
                        $match->away_team_id = $awayTeam->id;
                    } else {
                        $this->warn("Away team no encontrado: {$match->away_team}");
                    }
                }

                // Guardar si se actualizó algo
                if ($match->isDirty()) {
                    $match->save();
                    $updated++;
                }

            } catch (\Exception $e) {
                $this->error("Error procesando match {$match->id}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("✅ Completado: {$updated} partidos actualizados, {$failed} errores");
    }
}
