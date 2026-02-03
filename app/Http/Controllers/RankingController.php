<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Question;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    public function groupRanking(Group $group, Request $request)
    {
        // Si es una petición AJAX, retornar JSON con los resultados predictivos
        if ($request->expectsJson()) {
            return $this->getPredictiveResultsJson($group);
        }

        $rankings = $group->users()
            ->withSum(['answers as total_points' => function ($query) use ($group) {
                $query->whereHas('question', function ($questionQuery) use ($group) {
                    $questionQuery->where('group_id', $group->id);
                });
            }], 'points_earned')
            ->orderByDesc('total_points')
            ->get();

        return view('groups.show-unified', compact('group', 'rankings'));
    }

    private function getPredictiveResultsJson(Group $group)
    {
        $user = auth()->user();

        // Obtener respuestas del usuario (últimos 7 días de PARTIDOS, no respuestas)
        $sevenDaysAgo = now()->subDays(7);
        $answers = \App\Models\Answer::where('user_id', $user->id)
            ->whereHas('question', function ($query) use ($group, $sevenDaysAgo) {
                $query->where('group_id', $group->id)
                    ->where('type', 'predictive')
                    ->whereNotNull('result_verified_at')
                    ->whereHas('football_match', function ($matchQuery) use ($sevenDaysAgo) {
                        $matchQuery->where('date', '>=', $sevenDaysAgo);
                    });
            })
            ->with([
                'question' => function ($query) {
                    $query->with(['football_match', 'options']);
                },
                'questionOption'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        \Log::info('RankingController results', [
            'user_id' => $user->id,
            'group_id' => $group->id,
            'sevenDaysAgo' => $sevenDaysAgo->format('Y-m-d H:i:s'),
            'answers_count' => $answers->count(),
            'answer_ids' => $answers->pluck('id')->toArray(),
        ]);

        $groupedAnswers = $answers->groupBy(function ($answer) {
            return $answer->question->football_match ?
                $answer->question->football_match->date->format('Y-m-d') :
                $answer->created_at->format('Y-m-d');
        })->map(function ($group) {
            return $group->map(function ($answer) {
                $correctOption = $answer->question->options->where('is_correct', true)->first();
                return [
                    'id' => $answer->id,
                    'question' => [
                        'id' => $answer->question->id,
                        'title' => $answer->question->title,
                        'result_verified_at' => $answer->question->result_verified_at,
                        'football_match' => $answer->question->football_match ? [
                            'id' => $answer->question->football_match->id,
                            'home_team' => $answer->question->football_match->home_team,
                            'away_team' => $answer->question->football_match->away_team,
                            'status' => $answer->question->football_match->status,
                        ] : null,
                    ],
                    'question_option' => [
                        'id' => $answer->questionOption->id,
                        'text' => $answer->questionOption->text,
                    ],
                    'correct_option' => $correctOption ? [
                        'id' => $correctOption->id,
                        'text' => $correctOption->text,
                    ] : null,
                    'is_correct' => $answer->is_correct,
                    'points_earned' => $answer->points_earned,
                    'created_at' => $answer->created_at,
                ];
            })->values();
        })->toArray();

        $stats = [
            'total_answers' => $answers->count(),
            'correct_answers' => $answers->filter(fn($a) => $a->is_correct)->count(),
            'total_points' => $answers->sum('points_earned'),
            'accuracy_percentage' => $answers->count() > 0
                ? round(($answers->filter(fn($a) => $a->is_correct)->count() / $answers->count()) * 100)
                : 0,
        ];

        return response()->json([
            'groupedAnswers' => $groupedAnswers,
            'stats' => $stats,
        ]);
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
            return back()->with('error', __('controllers.rankings.not_available_yet'));
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
