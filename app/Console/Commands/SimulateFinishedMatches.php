<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use App\Jobs\VerifyQuestionResultsJob;
use App\Jobs\UpdateAnswersPoints;
use Illuminate\Support\Facades\Log;

class SimulateFinishedMatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches:simulate-finished';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '⚠️  SOLO PARA TESTING LOCAL: Simula resultados aleatorios (NO USAR EN PRODUCCIÓN)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // ⚠️ ADVERTENCIA: Este comando solo debe usarse en desarrollo para testing
        // En producción, SIEMPRE usa: matches:process-recently-finished
        // que obtiene resultados verificados de API Football o Gemini
        
        if (app()->environment('production')) {
            $this->error('❌ Este comando NO debe ejecutarse en PRODUCCIÓN');
            $this->info('Usa: php artisan matches:process-recently-finished');
            return;
        }

        $this->warn('⚠️  ADVERTENCIA: Este comando genera resultados ALEATORIOS para TESTING ÚNICAMENTE');
        $this->info('En producción, siempre usa: matches:process-recently-finished');
        $this->info('');
        $this->info('=== SIMULANDO PARTIDOS TERMINADOS (SOLO PARA TESTING) ===');

        // Encontrar partidos que deberían haber terminado (hace 2+ horas)
        $matches = FootballMatch::whereNotIn('status', ['FINISHED', 'Match Finished'])
            ->where('date', '<=', now()->subHours(2))
            ->where('date', '>=', now()->subHours(24))
            ->get();

        $this->info("Partidos encontrados: {$matches->count()}");

        if ($matches->isEmpty()) {
            $this->info('No hay partidos para simular');
            return;
        }

        foreach ($matches as $match) {
            // Generar score aleatorio realista (SOLO PARA TESTING)
            $homeScore = rand(0, 4);
            $awayScore = rand(0, 4);

            $match->update([
                'status' => 'Match Finished',
                'home_team_score' => $homeScore,
                'away_team_score' => $awayScore,
                'score' => "{$homeScore} - {$awayScore}",
                'events' => "Partido simulado (TESTING ONLY): {$homeScore} goles del local, {$awayScore} del visitante",
                'statistics' => json_encode([
                    'source' => 'Simulated (testing only)',
                    'verified' => false,
                    'timestamp' => now()->toIso8601String()
                ])
            ]);

            $this->info("✅ {$match->home_team} {$homeScore}-{$awayScore} {$match->away_team}");
        }

        $this->info("\n=== DESPACHANDO JOBS DE VERIFICACIÓN ===");

        // Despachar job para verificar resultados
        VerifyQuestionResultsJob::dispatch()->delay(now()->addSeconds(5));
        $this->info('✅ Job de verificación despachado');

        $this->warn("\n⚠️  RECORDATORIO: Estos son resultados SIMULADOS para TESTING");
        $this->info("Para producción, usa: php artisan matches:process-recently-finished");
    }
}
