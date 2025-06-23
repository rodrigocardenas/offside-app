<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FootballService;
use App\Services\OpenAIService;
use App\Jobs\UpdateMatchesAndVerifyResults;
use App\Models\Group;
use App\Traits\HandlesQuestions;

class UpdateFootballData extends Command
{
    use HandlesQuestions;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-football-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar datos de partidos de fútbol y verificar resultados';

    /**
     * Execute the console command.
     */
    public function handle(FootballService $footballService, OpenAIService $openAIService)
    {
        $this->info('Iniciando actualización de datos de fútbol...');

        try {
            // Dispatch the job to update matches and verify results
            UpdateMatchesAndVerifyResults::dispatch($footballService, $openAIService);

            $this->info('Actualización programada exitosamente.');
        } catch (\Exception $e) {
            $this->error('Error al actualizar datos de fútbol: ' . $e->getMessage());
        }
    }
}
