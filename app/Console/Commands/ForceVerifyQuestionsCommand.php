<?php

namespace App\Console\Commands;

use App\Jobs\BatchGetScoresJob;
use App\Jobs\BatchExtractEventsJob;
use App\Jobs\VerifyAllQuestionsJob;
use App\Models\FootballMatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Throwable;

class ForceVerifyQuestionsCommand extends Command
{
    protected $signature = 'app:force-verify-questions {--days=30} {--limit=100} {--match-id=} {--dry-run}';

    protected $description = 'Force verification of questions para partidos más antiguos. Útil para re-procesar matches que no se verificaron automáticamente.';

    public function handle(): int
    {
        // Validación y descripción de opciones
        $this->line('');
        $this->line('📖 USAGE:');
        $this->line('  php artisan app:force-verify-questions [OPTIONS]');
        $this->line('');
        $this->line('📋 OPTIONS:');
        $this->line('  --days=N       Número de días hacia atrás (default: 30)');
        $this->line('  --limit=N      Máximo de matches a verificar (default: 100)');
        $this->line('  --match-id=ID  ID específico del match (omite otros filtros)');
        $this->line('  --dry-run      Solo previsualizar sin ejecutar');
        $this->line('');

        $daysBack = $this->option('days') ?? 30;
        $limit = $this->option('limit') ?? 100;
        $matchId = $this->option('match-id');
        $dryRun = $this->option('dry-run');

        $this->info("🔍 FORCE VERIFY QUESTIONS");
        $this->info("═══════════════════════════════════════════════════════════════");
        $this->info("Days back: $daysBack");
        $this->info("Limit: $limit");
        $this->info("Match ID: " . ($matchId ?? 'ANY'));
        $this->info("Dry Run: " . ($dryRun ? 'YES' : 'NO'));
        $this->info("═══════════════════════════════════════════════════════════════\n");

        try {
            // Construir query
            $query = FootballMatch::query()
                ->withCount(['questions as pending_questions_count' => function ($q) {
                    $q->whereNull('result_verified_at');
                }])
                ->whereIn('status', ['Match Finished', 'FINISHED', 'Finished']);

            // Filtrar por match específico o rango de fechas
            if ($matchId) {
                $query->where('id', $matchId);
                $this->info("Buscando match específico: #$matchId\n");
            } else {
                $windowStart = now()->subDays($daysBack);
                $query->where('date', '>=', $windowStart);
                $this->info("Buscando matches desde: " . $windowStart->format('Y-m-d H:i') . "\n");
            }

            // Tener preguntas pendientes
            $query->whereHas('questions', function ($q) {
                $q->whereNull('result_verified_at');
            });

            $matches = $query
                ->orderByDesc('updated_at')
                ->limit($limit)
                ->get();

            if ($matches->isEmpty()) {
                $this->warn("❌ No matches found con criterios especificados");
                return 1;
            }

            $this->info("✅ Encontrados " . $matches->count() . " matches con preguntas pendientes:\n");

            // Mostrar detalles
            foreach ($matches as $match) {
                $pending = $match->pending_questions_count ?? 0;
                $verified = $match->questions()->whereNotNull('result_verified_at')->count();
                $total = $match->questions->count();

                $this->info("  Match #{$match->id}");
                $this->info("    • {$match->home_team} vs {$match->away_team} ({$match->home_team_score}-{$match->away_team_score})");
                $this->info("    • Fecha: " . $match->date->format('Y-m-d H:i'));
                $this->info("    • Status: {$match->status}");
                $this->info("    • Preguntas: $verified/$total verificadas ($pending pendientes)");
                $this->info("");
            }

            if ($dryRun) {
                $this->warn("⚠️  DRY RUN MODE: No se ejecutará la verificación");
                $this->newLine();
                $count = $matches->count();
                $this->info("ℹ️  Para ejecutar realmente, corre sin --dry-run");
                $this->info("php artisan app:force-verify-questions --days=$daysBack --limit=$limit");
                return 0;
            }

            // Ejecutar verificación
            $matchIds = $matches->pluck('id')->all();
            $batchId = Str::uuid()->toString();

            $this->info("🚀 DISPATCHING VERIFICATION BATCH");
            $this->info("Batch ID: $batchId");
            $this->info("Matches: " . count($matchIds));
            $this->newLine();

            FootballMatch::whereIn('id', $matchIds)->update([
                'last_verification_attempt_at' => now(),
            ]);

            Bus::batch([
                new BatchGetScoresJob($matchIds, $batchId),
                new BatchExtractEventsJob($matchIds, $batchId),
            ])
                ->catch(function ($batch, Throwable $exception) use ($batchId) {
                    $this->error("❌ Batch error: " . $exception->getMessage());
                })
                ->finally(function ($batch) use ($matchIds, $batchId) {
                    if ($batch->failed()) {
                        $this->warn("⚠️  Some jobs failed, but continuing with verification...");
                    }
                    dispatch(new VerifyAllQuestionsJob($matchIds, $batchId));
                })
                ->name('force-verify-' . $batchId)
                ->dispatch();

            $this->info("✅ Verification batch dispatched successfully");
            $this->info("📊 Queue will process: BatchGetScoresJob → BatchExtractEventsJob → VerifyAllQuestionsJob");
            $this->info("\n✨ Verificación en proceso. Revisa los logs con:");
            $this->info("   tail -f storage/logs/laravel.log | grep $batchId");

            return 0;
        } catch (Throwable $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
