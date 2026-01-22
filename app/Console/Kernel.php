<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\UpdateFootballData;
use App\Jobs\VerifyFinishedMatchesHourlyJob;
use App\Jobs\UpdateFinishedMatchesJob;
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

        // PIPELINE DIARIO:
        // 1️⃣ Cada hora: Actualizar status de partidos terminados (API Football + Gemini)
        $schedule->job(new UpdateFinishedMatchesJob())
            ->hourly()
            ->name('update-finished-matches')
            ->withoutOverlapping(10)
            ->timezone('America/Mexico_City')
            ->onSuccess(function () {
                Log::info('✅ update-finished-matches completado: partidos actualizados desde API y Gemini');
            })
            ->onFailure(function ($exception) {
                Log::error('❌ update-finished-matches falló', [
                    'error' => $exception->getMessage(),
                ]);
            });

        // 2️⃣ Cada hora (5 minutos después): Verificar respuestas de partidos ya terminados
        // Este job DEPENDE de que UpdateFinishedMatchesJob haya marcado los partidos como FINISHED
        $schedule->job(new VerifyFinishedMatchesHourlyJob())
            ->hourly()
            ->timezone('America/Mexico_City')
            ->at(':05')  // 5 minutos después de la hora
            ->name('verify-matches-hourly')
            ->withoutOverlapping(15)
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
