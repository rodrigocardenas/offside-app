<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VerificationRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerificationDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $hours = $this->sanitizeHours($request->integer('hours', 24));
        $dashboard = $this->buildDashboardData($hours);

        return view('admin.verification-dashboard', [
            'hours' => $hours,
            'dashboard' => $dashboard,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $hours = $this->sanitizeHours($request->integer('hours', 24));

        return response()->json([
            'hours' => $hours,
            'data' => $this->buildDashboardData($hours),
        ]);
    }

    private function sanitizeHours(?int $hours): int
    {
        $hours = $hours ?? 24;
        $hours = max(1, $hours);
        return min(168, $hours);
    }

    private function buildDashboardData(int $hours): array
    {
        $since = now()->subHours($hours);

        $runs = VerificationRun::where('started_at', '>=', $since)
            ->orderByDesc('started_at')
            ->get();

        $total = $runs->count();
        $success = $runs->where('status', 'success')->count();
        $failed = $total - $success;
        $avgDurationMs = round((float) ($runs->avg('duration_ms') ?? 0), 2);
        $successRate = $total > 0 ? round(($success / $total) * 100, 1) : 0;

        $lastRun = $runs->first();
        $lastFailure = $runs->firstWhere('status', '!=', 'success');

        $perJob = $runs->groupBy('job_name')
            ->map(function ($items) {
                $total = $items->count();
                $success = $items->where('status', 'success')->count();
                $failed = $total - $success;
                $avgMs = round((float) ($items->avg('duration_ms') ?? 0), 2);
                $successRate = $total > 0 ? round(($success / $total) * 100, 1) : 0;

                return [
                    'job_name' => $items->first()->job_name,
                    'total' => $total,
                    'success' => $success,
                    'failed' => $failed,
                    'success_rate' => $successRate,
                    'avg_duration_ms' => $avgMs,
                    'avg_duration_seconds' => $avgMs ? round($avgMs / 1000, 2) : 0,
                    'last_run_at' => optional($items->first())->started_at,
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        $recentRuns = $runs->take(12)
            ->map(fn (VerificationRun $run) => $this->transformRun($run))
            ->values()
            ->all();

        $recentFailures = $runs->filter(fn (VerificationRun $run) => $run->status !== 'success')
            ->take(6)
            ->map(fn (VerificationRun $run) => $this->transformRun($run))
            ->values()
            ->all();

        return [
            'summary' => [
                'total_runs' => $total,
                'success_count' => $success,
                'failure_count' => $failed,
                'success_rate' => $successRate,
                'avg_duration_ms' => $avgDurationMs,
                'avg_duration_seconds' => $avgDurationMs ? round($avgDurationMs / 1000, 2) : 0,
                'last_run_at' => optional($lastRun)->started_at,
                'last_failure_at' => optional($lastFailure)->started_at,
            ],
            'per_job' => $perJob,
            'recent_runs' => $recentRuns,
            'recent_failures' => $recentFailures,
        ];
    }

    private function transformRun(VerificationRun $run): array
    {
        return [
            'id' => $run->id,
            'job_name' => $run->job_name,
            'status' => $run->status,
            'batch_id' => $run->batch_id,
            'metrics' => $run->metrics ?? [],
            'context' => $run->context ?? [],
            'error_message' => $run->error_message,
            'duration_ms' => $run->duration_ms,
            'duration_seconds' => $run->duration_ms ? round($run->duration_ms / 1000, 2) : null,
            'started_at' => $run->started_at,
            'finished_at' => $run->finished_at,
        ];
    }
}
