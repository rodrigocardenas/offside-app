<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Competition;
use App\Models\FootballMatch;
use App\Models\Group;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\TemplateQuestion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WorldCupSocialController extends Controller
{
    private const TEMPLATE_QUESTION_ID = 44;

    // ─────────────────────────────────────────────────────────────────────────
    // GET /wc/
    // Lista de partidos WC de hoy y mañana
    // ─────────────────────────────────────────────────────────────────────────
    public function hoy()
    {
        $wcCompetition = Competition::where('type', 'WC')->first();

        $matches = FootballMatch::with(['homeTeam', 'awayTeam'])
            ->where('status', 'Not Started')
            ->where('date', '>=', now()->utc()->startOfDay())
            ->where('date', '<=', now()->utc()->addDay()->endOfDay())
            ->when($wcCompetition, fn($q) => $q->where('competition_id', $wcCompetition->id))
            ->when(!$wcCompetition, fn($q) => $q->where('league', 'WC'))
            ->orderBy('date', 'asc')
            ->get();

        return view('mundial.hoy', compact('matches'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /wc/{match}
    // Landing pública del partido
    // ─────────────────────────────────────────────────────────────────────────
    public function landing(FootballMatch $match)
    {
        // Seguridad: solo partidos del Mundial
        abort_unless($this->isWcMatch($match), 404);

        $match->loadMissing(['homeTeam', 'awayTeam']);

        $wcGroup   = Group::worldCup()->first();
        $question  = $wcGroup ? $this->getOrCreateQuestion($wcGroup, $match) : null;
        $userAnswer = null;

        if (auth()->check() && $question) {
            $userAnswer = Answer::where('user_id', auth()->id())
                ->where('question_id', $question->id)
                ->with('questionOption')
                ->first();
        }

        return view('mundial.landing', compact('match', 'question', 'userAnswer', 'wcGroup'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /wc/{match}/auth
    // Login rápido desde la landing (solo username)
    // ─────────────────────────────────────────────────────────────────────────
    public function quickAuth(Request $request, FootballMatch $match)
    {
        abort_unless($this->isWcMatch($match), 404);

        $request->validate([
            'name' => 'required|string|max:30',
        ]);

        $wasCreated = false;
        $name = trim($request->name);

        $user = User::where('name', $name)->first()
             ?? User::where('unique_id', $name)->first();

        if (!$user) {
            $email   = $name . '@offsideclub.com';
            $counter = 1;
            while (User::where('email', $email)->exists()) {
                $email = $name . '_' . $counter . '@offsideclub.com';
                $counter++;
            }

            $user = User::create([
                'name'     => $name,
                'email'    => $email,
                'password' => Hash::make(Str::random(16)),
                'timezone' => $request->timezone ?? 'UTC',
            ]);
            $wasCreated = true;
            event(new Registered($user));
        }

        Auth::login($user);

        Log::info('[WC Social] quickAuth', ['user' => $user->name, 'match' => $match->id, 'new' => $wasCreated]);

        return redirect()->route('wc.match', $match->id);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /wc/{match}/votar    [auth]
    // Registra el voto y redirige a la pantalla de resultado
    // ─────────────────────────────────────────────────────────────────────────
    public function votar(Request $request, FootballMatch $match)
    {
        abort_unless($this->isWcMatch($match), 404);

        // El partido no debe haber comenzado
        if ($match->date->lte(now())) {
            return redirect()->route('wc.match', $match->id)
                ->with('error', 'El partido ya ha comenzado. No puedes cambiar tu predicción.');
        }

        $wcGroup  = Group::worldCup()->first();
        $question = $wcGroup ? $this->getOrCreateQuestion($wcGroup, $match) : null;

        abort_unless($question, 404);

        $request->validate([
            'question_option_id' => 'required|integer',
        ]);

        $optionId = intval($request->question_option_id);

        // Seguridad: la opción debe pertenecer a esta pregunta
        $option = QuestionOption::where('id', $optionId)
            ->where('question_id', $question->id)
            ->firstOrFail();

        Answer::updateOrCreate(
            ['user_id' => auth()->id(), 'question_id' => $question->id],
            ['question_option_id' => $option->id, 'points_earned' => 0]
        );

        Log::info('[WC Social] voto registrado', [
            'user_id'   => auth()->id(),
            'match'     => "{$match->home_team} vs {$match->away_team}",
            'option'    => $option->text,
        ]);

        return redirect()->route('wc.resultado', $match->id)
            ->with('voted_option', $option->text);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /wc/{match}/resultado    [auth]
    // Pantalla de compartir post-voto
    // ─────────────────────────────────────────────────────────────────────────
    public function resultado(FootballMatch $match)
    {
        abort_unless($this->isWcMatch($match), 404);

        $wcGroup  = Group::worldCup()->first();
        $question = $wcGroup
            ? Question::where('match_id', $match->id)
                ->where('group_id', $wcGroup->id)
                ->where('template_question_id', self::TEMPLATE_QUESTION_ID)
                ->first()
            : null;

        $userAnswer = null;
        if ($question) {
            $userAnswer = Answer::where('user_id', auth()->id())
                ->where('question_id', $question->id)
                ->with('questionOption')
                ->first();
        }

        // Si por algún motivo no tiene voto, redirigir a la landing
        if (!$userAnswer) {
            return redirect()->route('wc.match', $match->id);
        }

        $votedOption = session('voted_option', $userAnswer->questionOption->text ?? null);

        return view('mundial.resultado', compact('match', 'question', 'userAnswer', 'votedOption', 'wcGroup'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    private function isWcMatch(FootballMatch $match): bool
    {
        $wcCompetition = Competition::where('type', 'WC')->first();
        if ($wcCompetition) {
            return $match->competition_id === $wcCompetition->id;
        }
        return $match->league === 'WC';
    }

    private function getOrCreateQuestion(Group $group, FootballMatch $match): ?Question
    {
        $template = TemplateQuestion::find(self::TEMPLATE_QUESTION_ID);
        if (!$template) {
            return null;
        }

        $question = Question::firstOrCreate(
            [
                'match_id'             => $match->id,
                'group_id'             => $group->id,
                'template_question_id' => $template->id,
            ],
            [
                'type'           => 'predictive',
                'title'          => str_replace(
                    ['{{home_team}}', '{{away_team}}'],
                    [$match->home_team, $match->away_team],
                    $template->text
                ),
                'competition_id' => $match->competition_id,
                'available_until' => $match->date->utc()->format('Y-m-d H:i:s'),
                'points'         => 300,
                'is_featured'    => true,
            ]
        );

        if ($question->wasRecentlyCreated) {
            $rawOptions = is_string($template->options)
                ? json_decode($template->options, true)
                : (array) $template->options;

            foreach ($rawOptions as $opt) {
                $optText = str_replace(
                    ['{{home_team}}', '{{away_team}}'],
                    [$match->home_team, $match->away_team],
                    $opt['text']
                );
                QuestionOption::firstOrCreate(
                    ['question_id' => $question->id, 'text' => $optText],
                    ['is_correct' => false]
                );
            }
        }

        return $question->load('options');
    }
}
