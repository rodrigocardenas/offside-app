<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Competition;
use App\Models\Question;
use App\Models\Option;
use App\Services\FootballDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    protected $footballDataService;

    public function __construct(FootballDataService $footballDataService)
    {
        $this->footballDataService = $footballDataService;
    }

    public function index()
    {
        $groups = auth()->user()->groups()->with('creator', 'competition')->get();
        return view('groups.index', compact('groups'));
    }

    public function create()
    {
        $competitions = Competition::all();
        return view('groups.create', compact('competitions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'competition_id' => 'nullable|exists:competitions,id',
        ]);

        $group = Group::create([
            'name' => $request->name,
            'code' => Str::random(6),
            'created_by' => auth()->id(),
            'competition_id' => $request->competition_id,
        ]);

        $group->users()->attach(auth()->id());

        // Si el grupo tiene una competición asociada, generamos una pregunta predictiva
        if ($group->competition) {
            $this->createPredictiveQuestion($group);
        }

        return redirect()->route('groups.show', $group)
            ->with('success', 'Grupo creado exitosamente.');
    }

    protected function createPredictiveQuestion(Group $group)
    {
        // Mapeo de competiciones locales a IDs de Football-Data.org
        $competitionMapping = [
            'champions' => 2001,  // UEFA Champions League
            'laliga' => 2014,    // La Liga
            'premier' => 2021,   // Premier League
            // Agregar más mapeos según sea necesario
        ];

        $apiCompetitionId = $competitionMapping[$group->competition->type] ?? null;

        if (!$apiCompetitionId) {
            return;
        }

        $matches = $this->footballDataService->getNextMatchesByCompetition($apiCompetitionId);
        $importantMatch = $this->footballDataService->getImportantMatch($matches);
        $questionData = $this->footballDataService->generatePredictiveQuestion($importantMatch);

        if ($questionData) {
            $question = Question::create([
                'title' => $questionData['title'],
                'description' => $questionData['description'],
                'type' => $questionData['type'],
                'points' => 3, // Puntos por defecto para preguntas predictivas
                'group_id' => $group->id,
                'available_until' => $questionData['available_until'],
            ]);

            foreach ($questionData['options'] as $optionData) {
                Option::create([
                    'question_id' => $question->id,
                    'text' => $optionData['text'],
                    'is_correct' => $optionData['is_correct'],
                ]);
            }
        }
    }

    public function show(Group $group)
    {
        // Verificar que el usuario pertenece al grupo
        if (!$group->users()->where('user_id', auth()->id())->exists()) {
            return redirect()->route('dashboard')->with('error', 'No tienes acceso a este grupo.');
        }

        // Obtener el ranking histórico del grupo
        $rankings = DB::table('users')
            ->join('group_user', 'users.id', '=', 'group_user.user_id')
            ->join('answers', 'users.id', '=', 'answers.user_id')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->where('group_user.group_id', $group->id)
            ->where('questions.group_id', $group->id)
            ->select('users.id', 'users.name', DB::raw('SUM(answers.points_earned) as total_points'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_points')
            ->limit(5)
            ->get();

        // Obtener la pregunta diaria (si existe)
        $dailyQuestion = Question::where('group_id', $group->id)
            ->where('available_until', '>', now())
            ->whereDate('created_at', today())
            ->with(['options', 'answers.user'])
            ->first();

        // Si no hay pregunta diaria y el grupo tiene competición, intentamos crear una predictiva
        if (!$dailyQuestion && $group->competition) {
            $this->createPredictiveQuestion($group);
            // Recargamos la pregunta
            $dailyQuestion = Question::where('group_id', $group->id)
                ->where('available_until', '>', now())
                ->with(['options', 'answers.user'])
                ->first();
        }

        // Verificar si el usuario ya respondió
        $userAnswer = null;
        if ($dailyQuestion) {
            $userAnswer = $dailyQuestion->answers()
                ->where('user_id', auth()->id())
                ->first();
        }

        return view('groups.show', compact('group', 'rankings', 'dailyQuestion', 'userAnswer'));
    }

    public function join(Request $request)
    {
        $request->validate([
            'code' => 'required|string|exists:groups,code',
        ]);

        $group = Group::where('code', $request->code)->firstOrFail();

        if ($group->users()->where('user_id', auth()->id())->exists()) {
            return back()->with('error', 'Ya eres miembro de este grupo.');
        }

        $group->users()->attach(auth()->id());

        return redirect()->route('groups.show', $group)
            ->with('success', 'Te has unido al grupo exitosamente.');
    }

    public function leave(Group $group)
    {
        if ($group->created_by === auth()->id()) {
            return back()->with('error', 'No puedes abandonar un grupo que has creado.');
        }

        $group->users()->detach(auth()->id());

        return redirect()->route('groups.index')
            ->with('success', 'Has abandonado el grupo exitosamente.');
    }

    public function joinByInvite($code)
    {
        $group = Group::where('code', $code)->firstOrFail();

        // Verificar si el usuario ya está en el grupo
        if ($group->users()->where('user_id', auth()->id())->exists()) {
            return redirect()->route('groups.show', $group)
                ->with('info', 'Ya eres miembro de este grupo.');
        }

        // Agregar usuario al grupo
        $group->users()->attach(auth()->id());

        return redirect()->route('groups.show', $group)
            ->with('success', '¡Te has unido al grupo exitosamente!');
    }
}
