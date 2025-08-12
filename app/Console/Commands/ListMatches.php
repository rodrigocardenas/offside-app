<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;

class ListMatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches:list {--limit=10 : Número de partidos a mostrar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lista los partidos disponibles en la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');

        $matches = FootballMatch::orderBy('date', 'desc')
            ->limit($limit)
            ->get();

        if ($matches->isEmpty()) {
            $this->info('No hay partidos en la base de datos');
            return;
        }

        $this->info("Mostrando los últimos $limit partidos:");
        $this->info('');

        foreach ($matches as $match) {
            $this->info("ID: {$match->id}");
            $this->info("Equipos: {$match->home_team} vs {$match->away_team}");
            $this->info("Fecha: {$match->date}");
            $this->info("Status: {$match->status}");
            $this->info("Score: {$match->score}");
            $this->info("External ID: {$match->external_id}");
            $this->info('---');
        }
    }
}
