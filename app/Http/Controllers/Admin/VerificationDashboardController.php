<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FootballMatch;
use App\Models\Question;
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

        $recentMatches = $this->fetchRecentMatches();
        $recentQuestions = $this->fetchRecentVerifiedQuestions();

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
            'recent_matches' => $recentMatches,
            'recent_verified_questions' => $recentQuestions,
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

    private function fetchRecentMatches(): array
    {
        return FootballMatch::query()
            ->with(['competition:id,name'])
            ->withCount([
                'questions',
                'questions as verified_questions_count' => function ($query) {
                    $query->whereNotNull('result_verified_at');
                },
                'questions as pending_questions_count' => function ($query) {
                    $query->whereNull('result_verified_at');
                },
            ])
            ->whereIn('status', ['Match Finished', 'FINISHED'])
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get()
            ->map(fn (FootballMatch $match) => $this->transformMatch($match))
            ->all();
    }

    private function fetchRecentVerifiedQuestions(): array
    {
        return Question::query()
            ->with(['group:id,name', 'football_match.competition:id,name'])
            ->whereNotNull('result_verified_at')
            ->orderByDesc('result_verified_at')
            ->limit(8)
            ->get()
            ->map(fn (Question $question) => $this->transformQuestion($question))
            ->all();
    }

    private function transformMatch(FootballMatch $match): array
    {
        $competitionName = optional($match->competition)->name ?? $match->competition ?? $match->league;
        $matchDate = $match->match_date ?? $match->date;

        return [
            'id' => $match->id,
            'home_team' => $match->home_team,
            'away_team' => $match->away_team,
            'home_score' => $match->home_team_score,
            'away_score' => $match->away_team_score,
            'status' => $match->status,
            'competition' => $competitionName,
            'match_date' => $matchDate,
            'updated_at' => $match->updated_at,
            'last_attempt_at' => $match->last_verification_attempt_at,
            'questions_total' => (int) ($match->questions_count ?? 0),
            'questions_verified' => (int) ($match->verified_questions_count ?? 0),
            'questions_pending' => (int) ($match->pending_questions_count ?? 0),
        ];
    }

    private function transformQuestion(Question $question): array
    {
        $match = $question->football_match;

        return [
            'id' => $question->id,
            'title' => $question->title,
            'type' => $question->type,
            'points' => $question->points,
            'result_verified_at' => $question->result_verified_at,
            'group_name' => optional($question->group)->name,
            'match' => $match ? [
                'id' => $match->id,
                'home_team' => $match->home_team,
                'away_team' => $match->away_team,
                'home_score' => $match->home_team_score,
                'away_score' => $match->away_team_score,
                'status' => $match->status,
                'match_date' => $match->match_date ?? $match->date,
                'competition' => optional($match->competition)->name ?? $match->competition ?? $match->league,
            ] : null,
        ];
    }
}
