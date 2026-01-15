<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\UpdateFootballData;
use App\Jobs\VerifyFinishedMatchesHourlyJob;
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

        // Nuevo pipeline optimizado: verifica partidos cada hora utilizando lotes y caché
        $schedule->job(new VerifyFinishedMatchesHourlyJob())
            ->hourly()
            ->name('verify-matches-hourly')
            ->withoutOverlapping(15)
            ->timezone('America/Mexico_City')
            ->onSuccess(function () {
                Log::info('✅ verify-matches-hourly completado correctamente');
            })
            ->onFailure(function ($exception) {
                Log::error('❌ verify-matches-hourly falló', [
                    'error' => $exception->getMessage(),
                ]);
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
