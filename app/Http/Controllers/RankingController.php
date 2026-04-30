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

        // Phase 4 Optimization: Use cached group_user.points instead of recalculating SUM(answers.points_earned)
        // This uses the rankedUsers() scope which orders by group_user.points DESC
        $rankings = $group->rankedUsers()->get();

        return view('groups.show-unified', compact('group', 'rankings'));
    }

    private function getPredictiveResultsJson(Group $group)
    {
        $user = auth()->user();

        // 🔧 FIXED: Obtener TODAS las respuestas del usuario (verificadas y sin verificar)
        // El frontend mostrará el label apropiado basado en result_verified_at
        // - Si result_verified_at IS NULL → "Sin Verificar"
        // - Si result_verified_at IS NOT NULL && is_correct=false → "Respuesta Incorrecta"
        // - Si result_verified_at IS NOT NULL && is_correct=true → Solo checkmark
        $answers = \App\Models\Answer::where('user_id', $user->id)
            ->whereHas('question', function ($query) use ($group) {
                $query->where('group_id', $group->id)
                    ->where('type', 'predictive');
                // REMOVED: ->whereNotNull('result_verified_at');
            })
            ->with([
                'question' => function ($query) {
                    $query->with(['football_match', 'options']);
                },
                'questionOption'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $groupedAnswers = $answers->groupBy(function ($answer) {
            return $answer->question->football_match ?
                $answer->question->football_match->date->format('Y-m-d') :
                $answer->created_at->format('Y-m-d');
        })->map(function ($group) {
            return $group->map(function ($answer) {
                // 🔧 IMPORTANTE: Calcular opción correcta en todos los casos
                $correctOption = $answer->question->options->where('is_correct', true)->first();
                
                // ✅ Determinar si la pregunta ha sido verificada
                $isVerified = $answer->question->result_verified_at !== null;
                
                return [
                    'id' => $answer->id,
                    'question' => [
                        'id' => $answer->question->id,
                        'title' => $answer->question->title,
                        'result_verified_at' => $answer->question->result_verified_at,
                        'is_verified' => $isVerified,  // 🆕 Flag explícito
                        'football_match' => $answer->question->football_match ? [
                            'id' => $answer->question->football_match->id,
                            'home_team' => $answer->question->football_match->home_team,
                            'away_team' => $answer->question->football_match->away_team,
                            'status' => $answer->question->football_match->status,
                            'date' => $answer->question->football_match->date->format('Y-m-d'),
                        ] : null,
                    ],
                    'question_option' => [
                        'id' => $answer->questionOption->id,
                        'text' => $answer->questionOption->text,
                    ],
                    // 🔧 CRÍTICO: Incluir opción correcta SIEMPRE
                    'correct_option' => $correctOption ? [
                        'id' => $correctOption->id,
                        'text' => $correctOption->text,
                    ] : null,
                    'is_correct' => $answer->is_correct,  // null si no verificado, true/false si verificado
                    'is_verified' => $isVerified,  // Flag de verificación
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
