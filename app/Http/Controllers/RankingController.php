<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Question;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    public function groupRanking(Group $group)
    {
        $rankings = $group->users()
            ->withSum(['answers as total_points' => function ($query) use ($group) {
                $query->whereHas('question', function ($questionQuery) use ($group) {
                    $questionQuery->where('group_id', $group->id);
                });
            }], 'points_earned')
            ->orderByDesc('total_points')
            ->get();

        return view('rankings.group', compact('group', 'rankings'));
    }

    public function dailyRanking()
    {
        $today = Carbon::today();

        $rankings = User::withSum(['answers as total_points' => function($query) use ($today) {
                $query->whereDate('created_at', $today);
            }], 'points_earned')
            ->orderByDesc('total_points')
            ->get();

        return view('rankings.daily', compact('rankings'));
    }

    public function questionRanking(Question $question)
    {
        if ($question->available_until > Carbon::now()) {
            return back()->with('error', 'Los rankings aún no están disponibles.');
        }

        $rankings = User::withSum(['answers as total_points' => function($query) use ($question) {
                $query->where('question_id', $question->id);
            }], 'points_earned')
            ->orderByDesc('total_points')
            ->get();

        return view('rankings.question', compact('question', 'rankings'));
    }

    public function userStats(User $user)
    {
        $stats = [
            'total_points' => $user->answers()->sum('points_earned'),
            'correct_answers' => $user->answers()->where('is_correct', true)->count(),
            'total_answers' => $user->answers()->count(),
            'accuracy' => $user->answers()->count() > 0
                ? round(($user->answers()->where('is_correct', true)->count() / $user->answers()->count()) * 100, 2)
                : 0
        ];

        return view('rankings.user-stats', compact('user', 'stats'));
    }
}
