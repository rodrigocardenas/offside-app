<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessRecentlyFinishedMatchesJob;
use App\Jobs\UpdateFinishedMatchesJob;
use App\Jobs\VerifyQuestionResultsJob;
use App\Jobs\CreatePredictiveQuestionsJob;
use App\Jobs\ProcessMatchBatchJob;
use App\Models\FootballMatch;
use Illuminate\Support\Facades\Log;

class TestOptimizedJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-optimized-jobs {--job=all : Job específico a probar (all, coordinator, update-matches, verify-questions, create-questions, batch)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba los jobs optimizados para verificar que funcionan correctamente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jobType = $this->option('job');

        $this->info("Probando jobs optimizados - Tipo: $jobType");

        switch ($jobType) {
            case 'coordinator':
                $this->testCoordinatorJob();
                break;
            case 'update-matches':
                $this->testUpdateMatchesJob();
                break;
            case 'verify-questions':
                $this->testVerifyQuestionsJob();
                break;
            case 'create-questions':
                $this->testCreateQuestionsJob();
                break;
            case 'batch':
                $this->testBatchJob();
                break;
            case 'all':
            default:
                $this->testAllJobs();
                break;
        }
    }

    private function testCoordinatorJob()
    {
        $this->info('Probando job coordinador...');

        try {
            ProcessRecentlyFinishedMatchesJob::dispatch();
            $this->info('✅ Job coordinador despachado correctamente');
        } catch (\Exception $e) {
            $this->error('❌ Error al despachar job coordinador: ' . $e->getMessage());
        }
    }

    private function testUpdateMatchesJob()
    {
        $this->info('Probando job de actualización de partidos...');

        // Contar partidos que necesitan actualización
        $pendingMatches = FootballMatch::whereNotIn('status', ['FINISHED', 'Match Finished'])
            ->where('date', '<=', now()->subHours(2))
            ->where('date', '>=', now()->subHours(600))
            ->count();

        $this->info("Partidos pendientes de actualización: $pendingMatches");

        try {
            UpdateFinishedMatchesJob::dispatch();
            $this->info('✅ Job de actualización de partidos despachado correctamente');
        } catch (\Exception $e) {
            $this->error('❌ Error al despachar job de actualización: ' . $e->getMessage());
        }
    }

    private function testVerifyQuestionsJob()
    {
        $this->info('Probando job de verificación de preguntas...');

        try {
            VerifyQuestionResultsJob::dispatch();
            $this->info('✅ Job de verificación de preguntas despachado correctamente');
        } catch (\Exception $e) {
            $this->error('❌ Error al despachar job de verificación: ' . $e->getMessage());
        }
    }

    private function testCreateQuestionsJob()
    {
        $this->info('Probando job de creación de preguntas...');

        try {
            CreatePredictiveQuestionsJob::dispatch();
            $this->info('✅ Job de creación de preguntas despachado correctamente');
        } catch (\Exception $e) {
            $this->error('❌ Error al despachar job de creación: ' . $e->getMessage());
        }
    }

    private function testBatchJob()
    {
        $this->info('Probando job de lotes...');

        // Obtener algunos IDs de partidos para probar
        $matchIds = FootballMatch::whereNotIn('status', ['FINISHED', 'Match Finished'])
            ->where('date', '<=', now()->subHours(2))
            ->limit(3)
            ->pluck('id')
            ->toArray();

        if (empty($matchIds)) {
            $this->warn('No hay partidos disponibles para probar el job de lotes');
            return;
        }

        $this->info("Probando con " . count($matchIds) . " partidos: " . implode(', ', $matchIds));

        try {
            ProcessMatchBatchJob::dispatch($matchIds, 1);
            $this->info('✅ Job de lotes despachado correctamente');
        } catch (\Exception $e) {
            $this->error('❌ Error al despachar job de lotes: ' . $e->getMessage());
        }
    }

    private function testAllJobs()
    {
        $this->info('Probando todos los jobs...');

        $this->testCoordinatorJob();
        $this->newLine();

        $this->testUpdateMatchesJob();
        $this->newLine();

        $this->testVerifyQuestionsJob();
        $this->newLine();

        $this->testCreateQuestionsJob();
        $this->newLine();

        $this->testBatchJob();

        $this->info('🎉 Pruebas completadas. Revisa los logs para ver el progreso.');
    }
}
