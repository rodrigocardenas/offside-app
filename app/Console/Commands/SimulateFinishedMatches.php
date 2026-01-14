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
    protected $description = 'Simula que partidos han terminado (para testing local) sin depender de la API externa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== SIMULANDO PARTIDOS TERMINADOS ===');

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
            // Generar score aleatorio realista
            $homeScore = rand(0, 4);
            $awayScore = rand(0, 4);

            $match->update([
                'status' => 'Match Finished',
                'home_team_score' => $homeScore,
                'away_team_score' => $awayScore,
                'score' => "{$homeScore} - {$awayScore}",
                'events' => "Partido simulado: {$homeScore} goles del local, {$awayScore} del visitante",
                'statistics' => json_encode([
                    'simulated' => true,
                    'timestamp' => now()->toIso8601String()
                ])
            ]);

            $this->info("✅ {$match->home_team} {$homeScore}-{$awayScore} {$match->away_team}");
        }

        $this->info("\n=== DESPACHANDO JOBS DE VERIFICACIÓN ===");

        // Despachar job para verificar resultados
        VerifyQuestionResultsJob::dispatch()->delay(now()->addSeconds(5));
        $this->info('✅ Job de verificación despachado');

        $this->info("\nEspera 10+ segundos y luego revisa los puntos de los usuarios");
        $this->info("Comando: php artisan queue:work --queue=default");
    }
}
