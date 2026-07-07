<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateFixturesNightly extends Command
{
    protected $signature = 'app:update-fixtures-nightly';

    protected $description = 'Actualizar fixtures de múltiples ligas cada noche (23:00)';

    public function handle()
    {
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('🌙 ACTUALIZACIÓN NOCTURNA DE FIXTURES');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        Log::info('Iniciando actualización nocturna de fixtures');

        $leagues = [
            // 🌍 Competencias Internacionales (Prioridad Alta)
            'world-cup' => 1,           // Mundial - próximas 4 semanas
        ];

        $totalMatches = 0;

        foreach ($leagues as $league => $daysAhead) {
            $this->newLine();
            $this->info("🔄 Actualizando {$league}...");

            try {
                // Llamar al comando de actualización individual
                $this->call('app:update-football-data', [
                    'league' => $league,
                    '--days-ahead' => $daysAhead,
                ]);

                $this->newLine();
                $this->info("✅ {$league} actualizada exitosamente");
                Log::info("Liga actualizada: {$league}");

            } catch (\Exception $e) {
                $this->error("❌ Error actualizando {$league}: " . $e->getMessage());
                Log::error("Error al actualizar {$league}", ['error' => $e->getMessage()]);
                continue;
            }

            // Pequeño delay entre ligas para no sobrecargar la API
            sleep(2);
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('✅ ACTUALIZACIÓN NOCTURNA COMPLETADA');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        Log::info('Actualización nocturna de fixtures completada');

        return Command::SUCCESS;
    }
}
