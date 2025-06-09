<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Competition;

class UpdateCompetitionCrestsCommand extends Command
{
    protected $signature = 'competitions:update-crests';
    protected $description = 'Actualiza las URLs de los escudos de las competiciones';

    public function handle()
    {
        $competitions = [
            // 'id' => 'url_del_escudo'
            1 => 'https://example.com/laliga.png',
            2 => 'https://example.com/premier.png',
            3 => 'https://example.com/champions.png',
            // Agrega aquí los IDs y URLs reales
        ];

        foreach ($competitions as $id => $crestUrl) {
            $competition = Competition::find($id);
            if ($competition) {
                $competition->crest_url = $crestUrl;
                $competition->save();
                $this->info("Escudo actualizado para: {$competition->name}");
            } else {
                $this->warn("Competencia con ID {$id} no encontrada.");
            }
        }
        $this->info('¡Escudos de competiciones actualizados!');
    }
}
