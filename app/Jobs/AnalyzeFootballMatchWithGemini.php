<?php

namespace App\Jobs;

use App\Models\FootballMatch;
use App\Models\GeminiAnalysis;
use App\Services\GeminiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class AnalyzeFootballMatchWithGemini implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [5, 10, 30]; // segundos
    public $timeout = 120;

    public FootballMatch $match;
    public string $analysisType;
    public ?int $userId = null;

    /**
     * Create a new job instance.
     */
    public function __construct(FootballMatch $match, string $analysisType = 'post_match', ?int $userId = null)
    {
        $this->match = $match;
        $this->analysisType = $analysisType;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(GeminiService $geminiService): void
    {
        try {
            Log::info("Iniciando análisis de Gemini para partido {$this->match->id} - Tipo: {$this->analysisType}");

            // Crear o actualizar registro de análisis
            $analysis = GeminiAnalysis::firstOrCreate(
                [
                    'football_match_id' => $this->match->id,
                    'analysis_type' => $this->analysisType,
                    'user_id' => $this->userId,
                ],
                [
                    'status' => 'pending',
                ]
            );

            // Marcar como procesando
            $analysis->markProcessing();
            $analysis->incrementAttempts();

            // Registrar tiempo de inicio
            $startTime = microtime(true);

            // Construir prompt según tipo de análisis
            $prompt = $this->buildPrompt($this->match);

            // Llamar a Gemini con grounding habilitado
            $result = $geminiService->callGemini($prompt, useGrounding: true);

            // Calcular tiempo de procesamiento
            $processingTime = (int)((microtime(true) - $startTime) * 1000);

            // Actualizar análisis con resultados
            $analysis->update([
                'status' => 'completed',
                'analysis_data' => $result,
                'summary' => $this->extractSummary($result),
                'grounding_sources' => $this->extractSources($result),
                'confidence_score' => 0.85, // Valor por defecto, puede calcularse según la respuesta
                'processing_time_ms' => $processingTime,
            ]);

            $analysis->markCompleted();

            Log::info(
                "Análisis completado para partido {$this->match->id}",
                [
                    'processing_time_ms' => $processingTime,
                    'analysis_id' => $analysis->id,
                ]
            );

        } catch (Exception $e) {
            Log::error("Error al analizar partido {$this->match->id}: " . $e->getMessage());

            if (isset($analysis)) {
                $analysis->markFailed($e->getMessage());
            }

            // Re-lanzar excepción para que Laravel reinente
            throw $e;
        }
    }

    /**
     * Construir el prompt según el tipo de análisis
     */
    private function buildPrompt(FootballMatch $match): string
    {
        $homeTeam = $match->homeTeam->name ?? 'Local';
        $awayTeam = $match->awayTeam->name ?? 'Visitante';
        $date = $match->date?->format('Y-m-d') ?? 'fecha desconocida';

        switch ($this->analysisType) {
            case 'pre_match':
                return "Realiza un análisis previo al partido entre {$homeTeam} vs {$awayTeam} programado para el {$date}. "
                    . "Busca información sobre: forma reciente de ambos equipos, últimos encuentros H2H, lesiones importantes, "
                    . "posible alineación, estadísticas clave. Proporciona una predicción del resultado probable y análisis táctico. "
                    . "Responde en JSON con estructura clara y cita tus fuentes.";

            case 'post_match':
                $homeScore = $match->home_score ?? '?';
                $awayScore = $match->away_score ?? '?';
                return "Analiza el resultado final del partido {$homeTeam} {$homeScore} - {$awayScore} {$awayTeam} jugado el {$date}. "
                    . "Incluye: eventos clave del partido, análisis de desempeño de jugadores destacados, estadísticas importantes, "
                    . "tácticas utilizadas, puntos decisivos, lesiones durante el encuentro. "
                    . "Responde en JSON con análisis detallado y cita tus fuentes.";

            default: // live
                return "Proporciona información en vivo sobre el partido {$homeTeam} vs {$awayTeam} jugándose ahora ({$date}). "
                    . "Incluye: marcador actual, eventos recientes, goles anotados, tarjetas mostradas, cambios realizados, "
                    . "posesión del balón, equipo que domina, predicción del resultado final. "
                    . "Responde en JSON con información actualizada.";
        }
    }

    /**
     * Extraer resumen de texto de la respuesta
     */
    private function extractSummary($result): ?string
    {
        if (is_array($result) && isset($result['content'])) {
            return substr($result['content'], 0, 500);
        }

        if (is_string($result)) {
            return substr($result, 0, 500);
        }

        return null;
    }

    /**
     * Extraer fuentes citadas de la respuesta (grounding)
     */
    private function extractSources($result): ?array
    {
        // Si la API proporciona grounding metadata, extraerlo aquí
        if (is_array($result) && isset($result['grounding_supports'])) {
            return $result['grounding_supports'];
        }

        return null;
    }

    /**
     * Manejar fallos del job
     */
    public function failed(Exception $exception): void
    {
        Log::error("Job de análisis falló permanentemente para partido {$this->match->id}", [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Actualizar análisis como fallido si existe
        $analysis = GeminiAnalysis::where('football_match_id', $this->match->id)
            ->where('analysis_type', $this->analysisType)
            ->where('user_id', $this->userId)
            ->first();

        if ($analysis) {
            $analysis->markFailed("Job falló después de " . $this->attempts() . " intentos: " . $exception->getMessage());
        }
    }
}

