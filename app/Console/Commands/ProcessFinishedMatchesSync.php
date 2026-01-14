<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\UpdateFinishedMatchesJob;
use App\Jobs\VerifyQuestionResultsJob;
use App\Jobs\CreatePredictiveQuestionsJob;
use App\Services\FootballService;
use App\Services\GeminiService;
use App\Services\QuestionEvaluationService;
use Illuminate\Support\Facades\Log;

class ProcessFinishedMatchesSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches:process-finished-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa partidos finalizados de forma síncrona (para testing local sin queue worker)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== PROCESAMIENTO SÍNCRONO DE PARTIDOS FINALIZADOS ===');

        try {
            // 1. Actualizar partidos
            $this->info("\n1️⃣ Actualizando partidos finalizados...");
            $footballService = app(FootballService::class);
            $geminiService = null;

            // Intentar inicializar GeminiService
            try {
                $geminiService = app(GeminiService::class);
                $this->info("   ℹ️  GeminiService disponible - se usará para obtener resultados reales");
            } catch (\Exception $e) {
                $this->warn("   ⚠️  GeminiService no disponible: " . $e->getMessage());
            }

            // Encontrar partidos que necesitan actualización
            $hoursBack = env('APP_ENV') === 'production' ? 24 : 72;
            $finishedMatches = \App\Models\FootballMatch::whereNotIn('status', ['FINISHED', 'Match Finished'])
                ->where('date', '<=', now()->subHours(2))
                ->where('date', '>=', now()->subHours($hoursBack))
                ->pluck('id')
                ->toArray();

            $this->info("Encontrados " . count($finishedMatches) . " partidos para procesar");

            if (!empty($finishedMatches)) {
                // Procesar en lotes de 5 sincronamente
                $batches = array_chunk($finishedMatches, 5);

                foreach ($batches as $batchNumber => $batch) {
                    $this->info("  → Procesando lote " . ($batchNumber + 1) . " (" . count($batch) . " partidos)");
                    $processJob = new \App\Jobs\ProcessMatchBatchJob($batch, $batchNumber + 1);
                    $processJob->handle($footballService, $geminiService);

                    if ($batchNumber < count($batches) - 1) {
                        sleep(1); // Pequeña pausa entre lotes
                    }
                }
            }

            $this->info("✅ Partidos actualizados");

            // 2. Esperar un poco
            sleep(2);

            // 3. Verificar preguntas
            $this->info("\n2️⃣ Verificando resultados de preguntas...");
            $evaluationService = app(QuestionEvaluationService::class);
            $verifyJob = new VerifyQuestionResultsJob();
            $verifyJob->handle($evaluationService);
            $this->info("✅ Preguntas verificadas");

            // 4. Esperar un poco
            sleep(2);

            // 5. Crear nuevas preguntas
            $this->info("\n3️⃣ Creando nuevas preguntas predictivas...");
            $createJob = new CreatePredictiveQuestionsJob();
            $createJob->handle();
            $this->info("✅ Nuevas preguntas creadas");

            $this->info("\n✅ ¡PROCESO COMPLETADO EXITOSAMENTE!");

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            Log::error("Error en ProcessFinishedMatchesSync", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
