<?php
namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\TemplateQuestion;
use App\Models\Answer;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\QuestionException;

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
            return back()->with('error', __('controllers.questions.not_available_yet'));
        }

        if ($question->available_until < Carbon::now()) {
            return back()->with('error', __('controllers.questions.no_longer_available'));
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

        // Limpiar cache relacionado con las preguntas que usan este template
        $questions = Question::where('template_question_id', $question->id)->get();
        foreach ($questions as $q) {
            Cache::forget("group_{$q->group_id}_match_questions");
            Cache::forget("group_{$q->group_id}_social_question");
            Cache::forget("group_{$q->group_id}_user_answers");
            Cache::forget("group_{$q->group_id}_show_data");
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
        if ($question->available_until->addHours(4) < Carbon::now()) {
            Log::info('No puedes responder a esta pregunta en este momento. Disponible desde: ' . $question->available_from . ' hasta: ' . $question->available_until);
            throw new QuestionException(
                'No puedes responder a esta pregunta en este momento.',
                $question->id,
                auth()->id(),
                'question_expired'
            );
        }

        // Validar que el partido aún no haya comenzado (si es pregunta predictiva)
        if ($question->type === 'predictive' && $question->football_match) {
            if ($question->football_match->date <= Carbon::now()) {
                Log::warning('Intento de responder pregunta predictiva después del inicio del partido', [
                    'user_id' => auth()->id(),
                    'question_id' => $question->id,
                    'match_date' => $question->football_match->date,
                    'current_time' => Carbon::now()
                ]);
                throw new QuestionException(
                    'No puedes responder esta predicción. El partido ya ha comenzado.',
                    $question->id,
                    auth()->id(),
                    'match_already_started'
                );
            }
        }

        $request->validate([
            'question_option_id' => 'required|exists:question_options,id',
        ]);

        // Usar updateOrCreate para crear o actualizar la respuesta
        Answer::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'question_id' => $question->id,
            ],
            [
                'question_option_id' => intval($request->question_option_id),
                'is_correct' => $question->type === 'social' ? true : null,
                'points_earned' => $question->type === 'social' ? 50 : 0,
                'category' => $question->type,
            ]
        );

        // limpiar cache de respuestas en ese grupo
        Cache::forget('user_answers_' . $question->group_id);
        Cache::forget("group_{$question->group_id}_match_questions");
        Cache::forget("group_{$question->group_id}_user_answers");
        Cache::forget("group_{$question->group_id}_show_data");

        Log::info('Respuesta guardada o actualizada: ' . $question->id . ' - ' . $request->question_option_id);

        return redirect()->route('groups.show', $question->group)->withFragment('question' . $question->id);
    }

    public function results(Question $question)
    {
        if ($question->available_until > Carbon::now()) {
            throw new QuestionException(
                'Los resultados aún no están disponibles.',
                $question->id,
                auth()->id(),
                'results_not_available'
            );
        }

        $answers = $question->answers()
            ->with('user')
            ->get()
            ->groupBy('option_id');

        return view('questions.results', compact('question', 'answers'));
    }
}
