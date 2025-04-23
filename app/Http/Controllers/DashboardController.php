<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $questions = Question::with(['options', 'answers'])
            ->where('available_until', '>', now())
            ->latest()
            ->get();

        $rankings = User::select('users.name', DB::raw('SUM(answers.points_earned) as total_points'))
            ->join('answers', 'users.id', '=', 'answers.user_id')
            ->whereDate('answers.created_at', today())
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_points')
            ->get();

        return view('dashboard', compact('questions', 'rankings'));
    }
}
