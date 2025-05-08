<?php

namespace App\Console\Commands;

use App\Services\Features\FeaturedMatchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateFeaturedMatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches:update-featured';
    
    /**
     * El servicio para gestionar partidos destacados
     *
     * @var FeaturedMatchService
     */
    protected $featuredMatchService;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los partidos destacados basados en criterios como clásicos, posición en la tabla, etc.';

    /**
     * Execute the console command.
     */
    /**
     * Crea una nueva instancia del comando
     *
     * @param FeaturedMatchService $featuredMatchService
     * @return void
     */
    public function __construct(FeaturedMatchService $featuredMatchService)
    {
        parent::__construct();
        $this->featuredMatchService = $featuredMatchService;
    }

    /**
     * Ejecuta el comando
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando actualización de partidos destacados...');
        
        try {
            $result = $this->featuredMatchService->updateFeaturedMatches();
            
            if ($result) {
                $this->info('✅ Partidos destacados actualizados correctamente.');
                return Command::SUCCESS;
            } else {
                $this->error('❌ Ocurrió un error al actualizar los partidos destacados.');
                Log::error('Error al actualizar partidos destacados desde el comando');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error inesperado: ' . $e->getMessage());
            Log::error('Error inesperado al actualizar partidos destacados: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return Command::FAILURE;
        }
    }
}
