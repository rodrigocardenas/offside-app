<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Answer;
use App\Models\Option;
use Illuminate\Http\Request;
use Carbon\Carbon;

class QuestionController extends Controller
{
    public function index()
    {
        $questions = Question::where('available_from', '<=', Carbon::now())
            ->where('available_until', '>=', Carbon::now())
            ->with(['answers' => function($query) {
                $query->where('user_id', auth()->id());
            }])
            ->get();

        return view('questions.index', compact('questions'));
    }

    public function show(Question $question)
    {
        if ($question->available_from > Carbon::now()) {
            return back()->with('error', 'Esta pregunta aún no está disponible.');
        }

        if ($question->available_until < Carbon::now()) {
            return back()->with('error', 'Esta pregunta ya no está disponible.');
        }

        $userAnswer = $question->answers()
            ->where('user_id', auth()->id())
            ->first();

        return view('questions.show', compact('question', 'userAnswer'));
    }

    public function answer(Request $request, Question $question)
    {
        if ($question->available_from > Carbon::now() || $question->available_until < Carbon::now()) {
            return back()->with('error', 'No puedes responder a esta pregunta en este momento.');
        }

        if ($question->answers()->where('user_id', auth()->id())->exists()) {
            return back()->with('error', 'Ya has respondido a esta pregunta.');
        }

        $request->validate([
            'option_id' => 'required|exists:options,id,question_id,' . $question->id,
        ]);

        $selectedOption = Option::findOrFail($request->option_id);
        $points = $selectedOption->is_correct ? $question->points : 0;

        Answer::create([
            'user_id' => auth()->id(),
            'question_id' => $question->id,
            'option_id' => $selectedOption->id,
            'is_correct' => $selectedOption->is_correct,
            'points_earned' => $points,
        ]);

        return redirect()->route('groups.show', $question->group)
            ->with('success', '¡Respuesta enviada exitosamente!');
    }

    public function results(Question $question)
    {
        if ($question->available_until > Carbon::now()) {
            return back()->with('error', 'Los resultados aún no están disponibles.');
        }

        $answers = $question->answers()
            ->with('user')
            ->get()
            ->groupBy('option_id');

        return view('questions.results', compact('question', 'answers'));
    }
}
