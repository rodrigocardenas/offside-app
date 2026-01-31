<?php

namespace App\Console\Commands;

use App\Jobs\BatchGetScoresJob;
use App\Jobs\BatchExtractEventsJob;
use App\Jobs\VerifyAllQuestionsJob;
use App\Models\FootballMatch;
use Carbon\Carbon;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class VerifyMatchesByDate extends Command
{
    protected $signature = 'app:verify-matches-by-date {--date= : Date in Y-m-d format (e.g., 2026-01-20)} {--start-date= : Start date (inclusive)} {--end-date= : End date (inclusive)} {--force : Skip confirmation}';
    protected $description = 'Force verification of matches from specific dates, bypassing normal filters';

    public function handle()
    {
        $this->line("\n╔════════════════════════════════════════════════════════════╗");
        $this->line("║ Verificación Forzada de Partidos por Fecha                  ║");
        $this->line("╚════════════════════════════════════════════════════════════╝\n");

        $date = $this->option('date');
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');

        // Build query
        $query = FootballMatch::query();

        if ($date) {
            $parsedDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
            $query->whereDate('date', $parsedDate);
            $this->info("Buscando partidos del: {$date}");
        } elseif ($startDate && $endDate) {
            $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();
            $query->whereBetween('date', [$start, $end]);
            $this->info("Buscando partidos entre: {$startDate} y {$endDate}");
        } else {
            $this->error('Debes especificar --date o ambos --start-date y --end-date');
            return;
        }

        // Find all matches (regardless of status or questions)
        $matches = $query->orderBy('date')->get();

        if ($matches->isEmpty()) {
            $this->warn('No se encontraron partidos para las fechas especificadas');
            return;
        }

        $this->line("\nPartidos encontrados: {$matches->count()}");
        $matches->each(function ($match) {
            $this->line("  • {$match->date->format('Y-m-d H:i')} - {$match->home_team} vs {$match->away_team} ({$match->status})");
        });

        // Count questions per match
        $this->line("\nPreguntas por partido:");
        foreach ($matches as $match) {
            $totalQuestions = $match->questions()->count();
            $pendingQuestions = $match->questions()->whereNull('result_verified_at')->count();
            $verifiedQuestions = $totalQuestions - $pendingQuestions;

            $this->line("  Match ID {$match->id}: {$totalQuestions} total ({$pendingQuestions} pending, {$verifiedQuestions} verified)");
        }

        // Confirm before proceeding
        if (!$this->option('force') && !$this->confirm("\n¿Deseas proceder con la verificación de estos partidos?")) {
            $this->info('Operación cancelada');
            return;
        }

        // Dispatch verification jobs IN SEQUENCE (not parallel!)
        // 1. BatchGetScoresJob → Update scores from API
        // 2. BatchExtractEventsJob → Enrich with detailed events if needed
        // 3. VerifyAllQuestionsJob → Verify questions
        $matchIds = $matches->pluck('id')->all();
        $batchId = Str::uuid()->toString();

        $this->info("\n🔄 Despachando trabajos de verificación en secuencia...");

        // Update last_verification_attempt_at
        FootballMatch::whereIn('id', $matchIds)->update([
            'last_verification_attempt_at' => now(),
        ]);

        // Despachar BatchGetScoresJob AHORA con forceRefresh=true para obtener eventos + estadísticas
        $getScoresJob = new BatchGetScoresJob($matchIds, $batchId, true);
        dispatch($getScoresJob);

        // Despachar BatchExtractEventsJob DESPUÉS de un delay (esperar a que BatchGetScoresJob termine)
        $extractEventsJob = new BatchExtractEventsJob($matchIds, $batchId);
        dispatch($extractEventsJob->delay(now()->addSeconds(60))); // Esperar 60 segundos

        // Despachar VerifyAllQuestionsJob DESPUÉS de otro delay
        $verifyJob = new VerifyAllQuestionsJob($matchIds, $batchId);
        dispatch($verifyJob->delay(now()->addSeconds(120))); // Esperar 2 minutos

        $this->line("\n╔════════════════════════════════════════════════════════════╗");
        $this->line("║ RESUMEN                                                    ║");
        $this->line("╠════════════════════════════════════════════════════════════╣");
        $this->line("║ Partidos a verificar: " . count($matchIds) . " ✅                          ║");
        $this->line("║ Batch ID: " . substr($batchId, 0, 8) . "...                                    ║");
        $this->line("║ Estado: Verificación despachada                            ║");
        $this->line("╚════════════════════════════════════════════════════════════╝\n");

        Log::info('VerifyMatchesByDate - verification started', [
            'match_count' => count($matchIds),
            'batch_id' => $batchId,
            'date_filter' => $date ?? "{$startDate} to {$endDate}",
        ]);
    }
}
