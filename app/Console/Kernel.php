<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\UpdateFootballData;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Obtener fixtures de múltiples ligas cada noche a las 23:00
        $schedule->command('app:update-fixtures-nightly')
            ->dailyAt('23:00')
            ->timezone('America/Mexico_City')
            ->onFailure(function () {
                Log::error('Error en la actualización nocturna de fixtures');
            });

        // Procesar partidos finalizados recientemente UNA SOLA VEZ AL DÍA en off-peak (3 AM)
        // IMPORTANTE: Se cambió de ->hourly() a ->dailyAt() para evitar bloquear el servidor
        // Cada ejecución toma hasta 10 minutos y consume muchos recursos
        $schedule->command('matches:process-recently-finished')
            ->dailyAt('03:00')
            ->timezone('America/Mexico_City')
            ->onFailure(function () {
                Log::error('Error en el procesamiento de partidos finalizados');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
