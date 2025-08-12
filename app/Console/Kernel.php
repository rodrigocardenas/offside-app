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
        $schedule->command('app:update-football-data')
            ->dailyAt('03:00')
            ->onFailure(function () {
                Log::error('Error en la actualización diaria de datos de fútbol');
            });

        $schedule->command('social-questions:renew')
            ->dailyAt('15:00')
            ->timezone('America/Mexico_City');

        // $schedule->command('matches:process-recently-finished')
        //     ->hourly()
        //     ->onFailure(function () {
        //         Log::error('Error en el procesamiento de partidos finalizados');
        //     });
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
