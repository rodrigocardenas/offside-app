<?php
namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\TemplateQuestion;
use App\Models\Answer;
use App\Models\Option;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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

    /**
     * Handle like/dislike reactions for a question
     */
    public function react(Request $request, TemplateQuestion $question)
    {
        $request->validate([
            'reaction' => 'required|in:like,dislike'
        ]);

        $user = auth()->user();
        $currentReaction = $question->getUserReaction($user);

        if ($currentReaction === $request->reaction) {
            // Si el usuario ya tiene la misma reacción, la eliminamos
            DB::table('template_question_user_reaction')
                ->where('user_id', $user->id)
                ->where('template_question_id', $question->id)
                ->delete();
        } else {
            // Si el usuario tiene una reacción diferente o ninguna, actualizamos o creamos
            DB::table('template_question_user_reaction')
                ->updateOrInsert(
                    [
                        'user_id' => $user->id,
                        'template_question_id' => $question->id
                    ],
                    [
                        'reaction' => $request->reaction,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
        }

        return response()->json([
            'success' => true,
            'likes' => $question->getLikesCount(),
            'dislikes' => $question->getDislikesCount(),
            'user_reaction' => $question->getUserReaction($user)
        ]);
    }

    public function answer(Request $request, Question $question)
    {
        if ($question->available_from > Carbon::now() || $question->available_until < Carbon::now()) {
            return back()->with('error', 'No puedes responder a esta pregunta en este momento.');
        }

        $request->validate([
            'option_id' => 'required|exists:options,id',
        ]);

        // Usar updateOrCreate para crear o actualizar la respuesta
        Answer::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'question_id' => $question->id,
            ],
            [
                'option_id' => intval($request->option_id),
                'is_correct' => $question->type === 'social' ? true : null,
                'points_earned' => $question->type === 'social' ? 100 : 0,
                'category' => $question->type,
            ]
        );

        Log::info('Respuesta guardada o actualizada: ' . $question->id . ' - ' . $request->option_id);

        return redirect()->route('groups.show', $question->group)->withFragment('question' . $question->id);
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
