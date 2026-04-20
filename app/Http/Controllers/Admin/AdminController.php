<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Team;
use App\Models\TemplateQuestion;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index()
    {
        $stats = [
            'questions_total' => Question::count(),
            'questions_featured' => Question::where('is_featured', true)->count(),
            'teams_total' => Team::count(),
            'template_questions_total' => TemplateQuestion::count(),
            'recent_questions' => Question::latest()->take(5)->get(),
            'recent_teams' => Team::latest()->take(5)->get(),
        ];

        return view('admin.dashboard', $stats);
    }
}
