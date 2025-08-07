<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Question;
use App\Services\OpenAIService;
use App\Services\FootballService;
use Illuminate\Support\Facades\Log;

class ProcessQuestionsWithRateLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'questions:process-with-rate-limit
                            {--batch-size=5 : Número de preguntas a procesar por lote}
                            {--delay=10 : Segundos de espera entre lotes}
                            {--max-questions=50 : Máximo número de preguntas a procesar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa preguntas pendientes con rate limiting para evitar saturar OpenAI';

    /**
     * Execute the console command.
     */
    public function handle(OpenAIService $openAIService, FootballService $footballService)
    {
        $batchSize = (int) $this->option('batch-size');
        $delayBetweenBatches = (int) $this->option('delay');
        $maxQuestions = (int) $this->option('max-questions');

        $this->info("=== PROCESAMIENTO DE PREGUNTAS CON RATE LIMITING ===");
        $this->info("Tamaño de lote: {$batchSize}");
        $this->info("Delay entre lotes: {$delayBetweenBatches} segundos");
        $this->info("Máximo de preguntas: {$maxQuestions}");
        $this->info("");

        // Obtener preguntas pendientes
        $pendingQuestions = Question::whereNull('result_verified_at')
            ->whereHas('football_match', function($query) {
                $query->whereIn('status', ['FINISHED', 'Match Finished']);
            })
            ->with(['football_match', 'options', 'answers'])
            ->take($maxQuestions)
            ->get();

        $totalQuestions = $pendingQuestions->count();
        $this->info("Preguntas pendientes encontradas: {$totalQuestions}");

        if ($totalQuestions === 0) {
            $this->info("No hay preguntas pendientes de procesar.");
            return;
        }

        $processedCount = 0;
        $successCount = 0;
        $errorCount = 0;

        // Procesar en lotes
        foreach ($pendingQuestions->chunk($batchSize) as $batchIndex => $batch) {
            $this->info("\n--- Procesando lote " . ($batchIndex + 1) . " ---");

            foreach ($batch as $question) {
                $processedCount++;
                $this->info("Procesando pregunta {$processedCount}/{$totalQuestions}: ID {$question->id}");
                $this->info("  Partido: {$question->football_match->home_team} vs {$question->football_match->away_team}");
                $this->info("  Título: {$question->title}");

                try {
                    // Actualizar el partido primero
                    $updatedMatch = $footballService->updateMatchFromApi($question->football_match->id);
                    if ($updatedMatch) {
                        $question->football_match = $updatedMatch;
                    }

                    $match = $question->football_match;
                    $answers = $question->answers;

                    // Verificar resultados usando OpenAI con rate limiting
                    $correctAnswers = $openAIService->verifyMatchResults(
                        [
                            'homeTeam' => $match->home_team,
                            'awayTeam' => $match->away_team,
                            'score' => $match->score,
                            'events' => $match->events
                        ],
                        [
                            [
                                'title' => $question->title,
                                'options' => $question->options->pluck('text')->toArray()
                            ]
                        ]
                    );

                                        // Convertir las respuestas correctas de texto a IDs de opciones
                    $correctOptionIds = [];
                    $correctOptions = [];

                    $this->info("  Respuesta de OpenAI: " . implode(', ', $correctAnswers->toArray()));
                    $this->info("  Opciones disponibles: " . implode(', ', $question->options->pluck('text')->toArray()));

                    foreach ($correctAnswers as $correctAnswerText) {
                        // Buscar coincidencias exactas primero
                        $exactMatch = $question->options->first(function($option) use ($correctAnswerText) {
                            return strtolower(trim($option->text)) === strtolower(trim($correctAnswerText));
                        });

                        if ($exactMatch) {
                            $correctOptionIds[] = $exactMatch->id;
                            $correctOptions[] = $exactMatch->text;
                            $this->info("  ✅ Coincidencia exacta: '{$exactMatch->text}'");
                            continue;
                        }

                        // Si no hay coincidencia exacta, buscar coincidencias parciales
                        $partialMatch = $question->options->first(function($option) use ($correctAnswerText) {
                            return stripos(trim($option->text), trim($correctAnswerText)) !== false ||
                                   stripos(trim($correctAnswerText), trim($option->text)) !== false;
                        });

                        if ($partialMatch) {
                            $correctOptionIds[] = $partialMatch->id;
                            $correctOptions[] = $partialMatch->text;
                            $this->info("  🔍 Coincidencia parcial: '{$partialMatch->text}' para '{$correctAnswerText}'");
                        } else {
                            $this->warn("  ⚠️ No se encontró coincidencia para: '{$correctAnswerText}'");
                        }
                    }

                    // Actualizar las opciones correctas en question_options
                    foreach ($question->options as $option) {
                        $wasCorrect = $option->is_correct;
                        $option->is_correct = in_array($option->id, $correctOptionIds);
                        $option->save();

                        if ($wasCorrect !== $option->is_correct) {
                            $this->info("  📝 Opción actualizada: '{$option->text}' - " . ($option->is_correct ? '✅ Correcta' : '❌ Incorrecta'));
                        }
                    }

                    // Actualizar las respuestas correctas en answers
                    $updatedAnswers = 0;
                    foreach ($answers as $answer) {
                        $wasCorrect = $answer->is_correct;
                        $answer->is_correct = in_array($answer->option_id, $correctOptionIds);
                        $answer->points_earned = $answer->is_correct ? 300 : 0;
                        $answer->save();

                        if ($wasCorrect !== $answer->is_correct) {
                            $updatedAnswers++;
                        }
                    }

                    // Marcar la pregunta como verificada
                    $question->result_verified_at = now();
                    $question->save();

                    $this->info("  ✅ Verificada correctamente. Respuestas actualizadas: {$updatedAnswers}");
                    $successCount++;

                } catch (\Exception $e) {
                    $this->error("  ❌ Error: " . $e->getMessage());
                    Log::error('Error al procesar pregunta ' . $question->id, [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $errorCount++;
                }

                // Pequeña pausa entre preguntas individuales
                if ($processedCount < $totalQuestions) {
                    sleep(1);
                }
            }

            // Pausa entre lotes
            if (($batchIndex + 1) * $batchSize < $totalQuestions) {
                $this->info("\nEsperando {$delayBetweenBatches} segundos antes del siguiente lote...");
                sleep($delayBetweenBatches);
            }
        }

        $this->info("\n=== RESUMEN ===");
        $this->info("Total procesadas: {$processedCount}");
        $this->info("Exitosas: {$successCount}");
        $this->info("Con errores: {$errorCount}");
        $this->info("Tasa de éxito: " . round(($successCount / $processedCount) * 100, 2) . "%");
    }
}
