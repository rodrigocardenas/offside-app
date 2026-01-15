<?php

namespace App\Console\Commands;

use App\Models\VerificationRun;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ReportVerificationMetrics extends Command
{
    protected $signature = 'verification:report
        {--hours=24 : Horas a considerar hacia atrás}
        {--job= : Filtrar por nombre de job}
        {--limit=50 : Máximo de ejecuciones a inspeccionar}';

    protected $description = 'Muestra métricas recientes de los jobs de verificación horaria';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $limit = max(1, (int) $this->option('limit'));
        $jobFilter = $this->option('job');

        $query = VerificationRun::query()
            ->orderByDesc('started_at')
            ->limit($limit);

        if ($hours > 0) {
            $query->where('started_at', '>=', now()->subHours($hours));
        }

        if ($jobFilter) {
            $query->where('job_name', $jobFilter);
        }

        $runs = $query->get();

        if ($runs->isEmpty()) {
            $this->warn('No se encontraron ejecuciones matching los filtros.');
            return self::SUCCESS;
        }

        $this->line("✅ Analizando {$runs->count()} ejecuciones recientes ({$hours}h)");

        $this->showJobSummary($runs);
        $this->showRecentFailures($runs);

        return self::SUCCESS;
    }

    protected function showJobSummary(Collection $runs): void
    {
        $rows = $runs
            ->groupBy('job_name')
            ->map(function (Collection $jobRuns, string $jobName) {
                $total = $jobRuns->count();
                $success = $jobRuns->where('status', 'success')->count();
                $failed = $jobRuns->where('status', 'failed')->count();
                $avgDuration = $jobRuns->whereNotNull('duration_ms')->avg('duration_ms') ?? 0;

                return [
                    'job' => $jobName,
                    'total' => $total,
                    'success' => $success,
                    'failed' => $failed,
                    'avg_seconds' => number_format($avgDuration / 1000, 2),
                ];
            })
            ->values()
            ->toArray();

        $this->table(
            ['Job', 'Total', 'Completados', 'Fallidos', 'Duración Prom (s)'],
            $rows
        );
    }

    protected function showRecentFailures(Collection $runs): void
    {
        $failures = $runs
            ->where('status', 'failed')
            ->sortByDesc('started_at')
            ->take(5);

        if ($failures->isEmpty()) {
            $this->info('🎉 No se registraron fallos en el rango solicitado.');
            return;
        }

        $rows = $failures->map(function ($run) {
            return [
                'job' => $run->job_name,
                'inicio' => optional($run->started_at)->toDateTimeString(),
                'batch_id' => $run->batch_id,
                'error' => $run->error_message ? substr($run->error_message, 0, 120) : 'N/A',
            ];
        })->toArray();

        $this->error('⚠️  Fallos recientes');
        $this->table(['Job', 'Inicio', 'Batch', 'Error'], $rows);
    }
}
