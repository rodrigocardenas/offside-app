<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Team;
use App\Models\FootballMatch;
use Illuminate\Support\Facades\DB;

class UpdateTeamsCompetition extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:update-competition';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza la competencia de los equipos basado en los partidos existentes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando actualización de competencias para equipos...');

        // Obtener todos los equipos
        $teams = Team::all();
        $bar = $this->output->createProgressBar(count($teams));
        $bar->start();

        foreach ($teams as $team) {
            // Buscar el primer partido donde el equipo participa por ID
            $match = FootballMatch::where('home_team_id', $team->id)
                ->orWhere('away_team_id', $team->id)
                ->whereNotNull('competition_id')
                ->first();

            if ($match) {
                $team->competition_id = $match->competition_id;
                $team->save();
                $this->info("\nEquipo {$team->name} actualizado con competencia ID: {$match->competition_id}");
            } else {
                $this->warn("\nNo se encontró partido para el equipo {$team->name}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('¡Actualización completada!');
    }
}
