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
            // 🌍 Competencias Internacionales (Prioridad Alta) - IDs verificados de api-sports.io
            2 => 21,           // UEFA Champions League - próximas 3 semanas
            3 => 21,           // UEFA Europa League - próximas 3 semanas
            6 => 21,           // Africa Cup of Nations - próximas 3 semanas
            12 => 14,          // CAF Champions League - próximas 2 semanas
            15 => 30,          // FIFA Club World Cup - próximas 4 semanas
            16 => 21,          // CONCACAF Champions League - próximas 3 semanas
            17 => 21,          // AFC Champions League - próximas 3 semanas
            22 => 21,          // CONCACAF Gold Cup - próximas 3 semanas
            27 => 21,          // OFC Champions League - próximas 3 semanas
            36 => 21,          // Africa Cup of Nations - Qualification - próximas 3 semanas
            
            // ⚽ Ligas Locales
            39 => 14,          // Premier League - próximas 2 semanas
            61 => 14,          // Ligue 1 - próximas 2 semanas
            71 => 14,          // Serie A (Brazil) - próximas 2 semanas
            78 => 14,          // Bundesliga - próximas 2 semanas
            135 => 14,         // Serie A (Italy) - próximas 2 semanas
            140 => 14,         // La Liga - próximas 2 semanas
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
