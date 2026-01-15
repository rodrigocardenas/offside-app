<?php

namespace App\Services\Admin;

use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Support\Facades\Cache;

class AppHealthMetricsService
{
    public function getDashboardData(int $hours, int $trendDays): array
    {
        $hours = $this->sanitizeHours($hours);
        $trendDays = $this->sanitizeTrendDays($trendDays);

        $cacheKey = sprintf('app-health-metrics-%s-%s', $hours, $trendDays);

        return Cache::remember($cacheKey, 60, function () use ($hours, $trendDays) {
            return $this->buildDashboardData($hours, $trendDays);
        });
    }

    private function buildDashboardData(int $hours, int $trendDays): array
    {
        $now = now();
        $sinceHours = $now->copy()->subHours($hours);
        $since24h = $now->copy()->subHours(24);
        $since7d = $now->copy()->subDays(7);

        $answersLastHours = Answer::where('created_at', '>=', $sinceHours)->count();
        $answersLast7d = Answer::where('created_at', '>=', $since7d)->count();
        $verifiedQuestions24h = Question::whereNotNull('result_verified_at')
            ->where('result_verified_at', '>=', $since24h)
            ->count();
        $newUsers24h = User::where('created_at', '>=', $since24h)->count();
        $newUsers7d = User::where('created_at', '>=', $since7d)->count();
        $logins24h = UserLogin::where('logged_in_at', '>=', $since24h)->count();

        $answersTrend = $this->buildDailySeries(Answer::class, 'created_at', $trendDays);
        $usersTrend = $this->buildDailySeries(User::class, 'created_at', $trendDays);

        return [
            'summary' => [
                'answers_last_hours' => $answersLastHours,
                'answers_last_7d' => $answersLast7d,
                'verified_questions_24h' => $verifiedQuestions24h,
                'new_users_24h' => $newUsers24h,
                'new_users_7d' => $newUsers7d,
                'logins_24h' => $logins24h,
            ],
            'trends' => [
                'answers' => $answersTrend,
                'new_users' => $usersTrend,
            ],
            'recent_answers' => $this->transformRecentAnswers(),
            'recent_users' => $this->transformRecentUsers(),
            'recent_logins' => $this->transformRecentLogins(),
        ];
    }

    private function transformRecentAnswers(): array
    {
        return Answer::query()
            ->with([
                'user:id,name,unique_id',
                'question:id,title,group_id',
                'question.group:id,name',
            ])
            ->latest()
            ->take(8)
            ->get()
            ->map(function (Answer $answer) {
                return [
                    'id' => $answer->id,
                    'title' => $answer->question?->title,
                    'group_name' => $answer->question?->group?->name,
                    'user' => $answer->user ? [
                        'id' => $answer->user->id,
                        'name' => $answer->user->name,
                        'unique_id' => $answer->user->unique_id,
                    ] : null,
                    'is_correct' => $answer->is_correct,
                    'points' => $answer->points_earned,
                    'answered_at' => $answer->created_at,
                ];
            })
            ->all();
    }

    private function transformRecentUsers(): array
    {
        return User::query()
            ->orderByDesc('created_at')
            ->select('id', 'name', 'email', 'unique_id', 'created_at')
            ->take(8)
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'unique_id' => $user->unique_id,
                    'created_at' => $user->created_at,
                ];
            })
            ->all();
    }

    private function transformRecentLogins(): array
    {
        return UserLogin::query()
            ->with('user:id,name,unique_id')
            ->orderByDesc('logged_in_at')
            ->take(10)
            ->get()
            ->map(function (UserLogin $login) {
                return [
                    'id' => $login->id,
                    'user' => $login->user ? [
                        'id' => $login->user->id,
                        'name' => $login->user->name,
                        'unique_id' => $login->user->unique_id,
                    ] : null,
                    'ip_address' => $login->ip_address,
                    'device' => $login->device,
                    'user_agent' => $login->user_agent,
                    'logged_in_at' => $login->logged_in_at,
                ];
            })
            ->all();
    }

    private function buildDailySeries(string $modelClass, string $column, int $days): array
    {
        $start = now()->copy()->subDays($days - 1)->startOfDay();
        $end = now()->copy()->endOfDay();

        $records = $modelClass::query()
            ->selectRaw('DATE(' . $column . ') as day, COUNT(*) as total')
            ->where($column, '>=', $start)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->pluck('total', 'day');

        $series = [];
        $cursor = $start->copy();
        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $cursor->format('Y-m-d');
            $series[] = [
                'date' => $key,
                'label' => $cursor->format('d M'),
                'total' => (int) ($records[$key] ?? 0),
            ];
            $cursor->addDay();
        }

        return $series;
    }

    private function sanitizeHours(?int $hours): int
    {
        $hours = $hours ?? 24;
        $hours = max(1, $hours);
        return min(168, $hours);
    }

    private function sanitizeTrendDays(?int $days): int
    {
        $days = $days ?? 7;
        $days = max(1, $days);
        return min(30, $days);
    }
}
