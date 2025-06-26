<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessRecentlyFinishedMatchesJob;

class ProcessRecentlyFinishedMatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches:process-recently-finished';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa partidos finalizados en la última hora, verifica preguntas y notifica a los usuarios.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Despachar el Job en segundo plano
        ProcessRecentlyFinishedMatchesJob::dispatch();
        $this->info('Job para procesar partidos finalizados recientemente despachado correctamente.');
    }
}
