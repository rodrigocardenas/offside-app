<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Competition;
use App\Models\Question;
use App\Models\Option;
use App\Models\QuestionOption;
use App\Models\User;
use App\Models\FootballMatch;
use App\Services\FootballDataService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\FootballService;
use Illuminate\Support\Facades\Http;

class GroupController extends Controller
{
    protected $footballDataService;
    protected $openAIService;
    protected $competitionMapping = [
        'champions' => 2001,  // UEFA Champions League
        'laliga' => 2014,    // La Liga
        'premier' => 2021,   // Premier League
    ];
    protected $questionTemplates = [
        [
            'template' => '¿Qué equipo anotará el primer gol en el partido {home} vs {away}?',
            'options' => [
                '{home}',
                '{away}',
                'Ningún equipo (0-0)'
            ]
        ],
        [
            'template' => '¿Habrá más de 2.5 goles en el partido {home} vs {away}?',
            'options' => [
                'Sí',
                'No'
            ]
        ],
        [
            'template' => '¿Habrá alguna tarjeta roja en el partido {home} vs {away}?',
            'options' => [
                'Sí',
                'No'
            ]
        ],
        [
            'template' => '¿Cuál será el resultado del partido {home} vs {away}?',
            'options' => [
                'Victoria {home}',
                'Empate',
                'Victoria {away}'
            ]
        ],
        [
            'template' => '¿En qué mitad se anotarán más goles en el partido {home} vs {away}?',
            'options' => [
                'Primera mitad',
                'Segunda mitad',
                'Igual cantidad en ambas'
            ]
        ]
    ];

    public function __construct(FootballDataService $footballDataService, OpenAIService $openAIService)
    {
        $this->footballDataService = $footballDataService;
        $this->openAIService = $openAIService;
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

        return redirect()->route('groups.show', $group)
            ->with('success', 'Grupo creado exitosamente.');
    }

    protected function createPredictiveQuestion(Group $group)
    {
        try {
            $matches = FootballMatch::where('league', $group->competition->type)
                ->where('status', 'Not Started')
                ->where('date', '>=', now())
                ->where('date', '<=', now()->addDays(7))
                ->orderBy('is_featured', 'desc')
                ->orderBy('date')
                ->limit(5)
                ->get();

            if ($matches->isEmpty()) {
                Log::warning('No se encontraron partidos para la competición:', [
                    'competition_type' => $group->competition->type
                ]);
                return collect();
            }

            $competitionType = $group->competition->type;
            $matchesData = collect($matches)->map(function($match) use ($competitionType) {
                if (!isset($match['id']) || !isset($match['home_team']) || !isset($match['away_team'])) {
                    Log::warning('Partido con datos incompletos:', ['match' => $match]);
                    return null;
                }

                return [
                    'id' => $match['id'],
                    'home_team' => is_array($match['home_team']) ? $match['home_team']['name'] : $match['home_team'],
                    'away_team' => is_array($match['away_team']) ? $match['away_team']['name'] : $match['away_team'],
                    'date' => $match['date'] ?? now(),
                    'competition' => $competitionType
                ];
            })->filter()->values()->toArray();

            if (empty($matchesData)) {
                Log::warning('No hay partidos válidos después de la validación');
                return collect();
            }

            $questions = $this->generateQuestionsForMatches($matchesData, $group);

            return $questions;
        } catch (\Exception $e) {
            Log::error('Error al crear pregunta predictiva:', [
                'error' => $e->getMessage(),
                'group_id' => $group->id
            ]);
            return collect();
        }
    }

    public function show(Group $group, FootballService $service)
    {
        $group->load(['competition', 'users' => function ($query) use ($group) {
            $query->withSum(['answers as total_points' => function ($query) use ($group) {
                $query->whereHas('question', function ($questionQuery) use ($group) {
                    $questionQuery->where('group_id', $group->id);
                });
            }], 'points_earned');
        }, 'chatMessages.user']);
        // dd($group->users->pluck('total_points'));

        if (!$group->users()->where('user_id', auth()->id())->exists()) {
            return redirect()->route('dashboard')->with('error', 'No tienes acceso a este grupo.');
        }

        $socialQuestion = Question::where('type', 'social')
            ->where('group_id', $group->id)
            ->where('available_until', '>', now())
            ->with(['answers.user', 'answers.option', 'options'])
            ->inRandomOrder()
            ->first();

        if ($group->users->count() <= 4 && $socialQuestion) {
            foreach ($group->users as $user) {
                QuestionOption::updateOrCreate([
                    'question_id' => $socialQuestion->id,
                    'text' => $user->name,
                ], [
                    'is_correct' => true
                ]);
            }
            $socialQuestion->refresh();
        }

        $matchQuestions = collect();
        $currentMatchday = null;

        if ($group->competition) {
            $matchQuestions = Question::where('type', 'predictive')
                ->where('group_id', $group->id)
                ->where('available_until', '>', now()->subHours(4))
                ->with(['options', 'answers.user', 'answers.option'])
                ->get();

            if ($matchQuestions->isEmpty()) {
                $createdQuestions = $this->createPredictiveQuestion($group);
                if ($createdQuestions) {
                    $matchQuestions = $matchQuestions->merge($createdQuestions);
                }
                $socialQuestion = $createdQuestions->where('type', 'social')->first();
            }
        }

        $matchQuestions = $matchQuestions->unique('id')->take(5);

        $userAnswers = auth()->user()->answers()
            ->whereIn('question_id', $matchQuestions->pluck('id'))
            ->when($socialQuestion, function ($query) use ($socialQuestion) {
                $query->orWhere('question_id', $socialQuestion->id);
            })
            ->pluck('option_id', 'question_id');

        $matchQuestions->each(function ($question) {
            if ($question->football_match) {
                $question->is_disabled = $question->football_match->status !== 'Not Started';
            } else {
                $question->is_disabled = $question->available_until->isPast();
            }
        });

        return view('groups.show', compact('group', 'matchQuestions', 'userAnswers', 'currentMatchday', 'socialQuestion'));
    }

    protected function generateMatchQuestions($match, $group)
    {
        $questions = collect();

        if (!isset($match['id']) || !isset($match['home_team']) || !isset($match['away_team'])) {
            Log::error('Datos de partido incompletos:', ['match' => $match]);
            return $questions;
        }

        $matchData = [
            'id' => $match['id'],
            'home_team' => is_array($match['home_team']) ? $match['home_team']['name'] : $match['home_team'],
            'away_team' => is_array($match['away_team']) ? $match['away_team']['name'] : $match['away_team'],
            'date' => $match['date'] ?? now(),
            'competition' => $group->competition->type
        ];

        try {
            $generatedQuestions = $this->openAIService->generateMatchQuestions(
                [$matchData],
                2,
                $group->competition->type
            );

            if (empty($generatedQuestions)) {
                Log::warning('No se generaron preguntas para el partido:', ['match' => $matchData]);
                return $questions;
            }

            foreach ($generatedQuestions as $questionData) {
                if (!isset($questionData['title']) || !isset($questionData['options'])) {
                    Log::warning('Datos de pregunta incompletos:', ['question' => $questionData]);
                    continue;
                }

                $availableUntil = null;
                try {
                    if (isset($matchData['date']) && $matchData['date'] !== 'Fecha del partido') {
                        $availableUntil = Carbon::parse($matchData['date']);
                    } else {
                        $availableUntil = now()->addDay();
                    }
                } catch (\Exception $e) {
                    Log::warning('Error al parsear fecha del partido, usando fecha por defecto', [
                        'date' => $matchData['date'],
                        'error' => $e->getMessage()
                    ]);
                    $availableUntil = now()->addDay();
                }

                $questions->push($question->load('options'));
            }

            return $questions;
        } catch (\Exception $e) {
            Log::error('Error al generar preguntas: ' . $e->getMessage(), [
                'match' => $matchData,
                'trace' => $e->getTraceAsString()
            ]);
            return $questions;
        }
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

    public function leaveGroup(Group $group)
    {
        if ($group->created_by === auth()->id() && $group->users()->count() === 1) {
            abort(403, 'No puedes abandonar un grupo que has creado');
        }

        auth()->user()->groups()->detach($group->id);

        return redirect()->route('groups.index')->with('success', 'Has abandonado el grupo exitosamente');
    }

    public function joinByInvite($code)
    {
        $group = Group::where('code', $code)->firstOrFail();

        if ($group->users()->where('user_id', auth()->id())->exists()) {
            return redirect()->route('groups.show', $group)
                ->with('info', 'Ya eres miembro de este grupo.');
        }

        $group->users()->attach(auth()->id());

        return redirect()->route('groups.show', $group)
            ->with('success', '¡Te has unido al grupo exitosamente!');
    }

    protected function getNextMatchdayMatches($competition)
    {
        $apiCompetitionId = $this->competitionMapping[$competition->type] ?? null;

        if (!$apiCompetitionId) {
            return collect();
        }

        try {
            $matches = $this->footballDataService->getNextMatchesByCompetition($apiCompetitionId);

            return collect($matches)->map(function ($match) {
                return [
                    'id' => $match->id ?? null,
                    'home_team' => $match->home_team->name ?? '',
                    'away_team' => $match->away_team->name ?? '',
                    'date' => $match->utcDate ?? now(),
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error al obtener partidos de la próxima jornada: ' . $e->getMessage());
            return collect();
        }
    }

    protected function createSocialQuestion(Group $group)
    {
        $group->load('users');

        if ($group->users->count() < 2) {
            return null;
        }

        $socialQuestion = Question::where('type', 'social')
            ->latest()
            ->first();

        if (!$socialQuestion) {
            return null;
        }

        $groupQuestion = Question::create([
            'title' => $socialQuestion->title,
            'description' => $socialQuestion->description,
            'type' => 'social',
            'points' => 0,
            'group_id' => $group->id,
            'available_until' => now()->addDay(),
        ]);

        foreach ($group->users as $user) {
            Option::create([
                'question_id' => $groupQuestion->id,
                'text' => $user->name,
                'is_correct' => false,
                'user_id' => $user->id,
            ]);
        }

        return $groupQuestion->load(['options', 'answers.user']);
    }

    public function testGenerateQuestions()
    {
        $matches = [
            [
                'home_team' => 'Real Madrid',
                'away_team' => 'Barcelona',
                'date' => '2024-05-01 20:00:00'
            ],
            [
                'home_team' => 'Atlético Madrid',
                'away_team' => 'Sevilla',
                'date' => '2024-05-01 18:00:00'
            ],
            [
                'home_team' => 'Athletic Bilbao',
                'away_team' => 'Real Sociedad',
                'date' => '2024-05-02 21:00:00'
            ]
        ];

        try {
            $questions = $this->openAIService->generateMatchQuestions($matches, 2, 'La Liga');
            return response()->json([
                'success' => true,
                'questions' => $questions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function generateQuestionsForMatches($matches, $group)
    {
        Log::info('Generando preguntas para partidos:', [
            'competition' => $group->competition->type,
            'total_matches' => count($matches)
        ]);

        $predictiveTemplates = \App\Models\TemplateQuestion::where('type', 'predictive')
            ->whereNull('used_at')
            ->orderBy('is_featured', 'desc')
            ->orderBy('id')
            ->take(count($matches))
            ->get();

        $socialTemplates = \App\Models\TemplateQuestion::where('type', 'social')
            ->whereNull('used_at')
            ->orderBy('is_featured', 'desc')
            ->first();

        $questions = collect();

        usort($matches, function($a, $b) {
            $aFeatured = $a['is_featured'] ?? false;
            $bFeatured = $b['is_featured'] ?? false;

            if ($aFeatured && !$bFeatured) return -1;
            if (!$aFeatured && $bFeatured) return 1;
            return 0;
        });

        foreach ($matches as $index => $match) {
            if (isset($predictiveTemplates[$index])) {
                $template = $predictiveTemplates[$index];
                $question = $this->createQuestionFromTemplate($template, $match, $group);
                if ($question) {
                    $questions->push($question);
                }
            }
        }

        if ($socialTemplates && count($matches) > 0) {
            $socialQuestion = $this->createQuestionFromTemplate($socialTemplates, $matches[0], $group);
            if ($socialQuestion) {
                $questions->push($socialQuestion);
            }
        }

        return $questions;
    }

    protected function createQuestionFromTemplate($template, $match, $group)
    {
        try {
            if (!is_object($template)) {
                Log::warning('Template no es un objeto:', [
                    'template' => $template,
                    'match_id' => $match['id']
                ]);
                return null;
            }

            if (!isset($template->text) || !isset($template->type) || !isset($template->options)) {
                Log::warning('Template incompleto:', [
                    'template' => $template,
                    'match_id' => $match['id']
                ]);
                return null;
            }

            $questionText = str_replace(
                ['{{home_team}}', '{{away_team}}'],
                [$match['home_team'], $match['away_team']],
                $template->text
            );

            $options = collect($template->options)->map(function ($option) use ($match) {
                if (!isset($option['text'])) {
                    Log::warning('Opción sin texto:', [
                        'option' => $option,
                        'match_id' => $match['id']
                    ]);
                    return null;
                }

                $optionText = str_replace(
                    ['{{ home_team }}', '{{ away_team }}'],
                    [$match['home_team'], $match['away_team']],
                    $option['text']
                );
                return [
                    'text' => $optionText,
                    'is_correct' => $option['is_correct'] ?? false
                ];
            })->filter()->toArray();

            if (strpos($questionText, 'jugador') !== false) {
                try {
                    $homeTeamPlayers = $this->footballDataService->getTeamPlayers($match['{{home_team}}']);
                    $awayTeamPlayers = $this->footballDataService->getTeamPlayers($match['{{away_team}}']);

                    $randomHomeKeys = array_rand($homeTeamPlayers, min(2, count($homeTeamPlayers)));
                    $randomAwayKeys = array_rand($awayTeamPlayers, min(2, count($awayTeamPlayers)));

                    $randomHomeKeys = is_array($randomHomeKeys) ? $randomHomeKeys : [$randomHomeKeys];
                    $randomAwayKeys = is_array($randomAwayKeys) ? $randomAwayKeys : [$randomAwayKeys];

                    $options = [
                        ['text' => $homeTeamPlayers[$randomHomeKeys[0]]['player']['name'] ?? 'Jugador 1', 'is_correct' => false],
                        ['text' => $homeTeamPlayers[$randomHomeKeys[1] ?? $randomHomeKeys[0]]['player']['name'] ?? 'Jugador 2', 'is_correct' => false],
                        ['text' => $awayTeamPlayers[$randomAwayKeys[0]]['player']['name'] ?? 'Jugador 3', 'is_correct' => false],
                        ['text' => $awayTeamPlayers[$randomAwayKeys[1] ?? $randomAwayKeys[0]]['player']['name'] ?? 'Jugador 4', 'is_correct' => false]
                    ];

                    shuffle($options);
                } catch (\Exception $e) {
                    Log::error('Error al obtener jugadores:', [
                        'error' => $e->getMessage(),
                        'match' => $match
                    ]);
                    return null;
                }
            }

            $availableUntil = \Carbon\Carbon::parse($match['date'])
                ->setTimezone('UTC')
                ->format('Y-m-d H:i:s');

            $questionData = [
                'type' => $template->type,
                'title' => $questionText,
                'description' => $questionText,
                'competition_id' => $group->competition_id,
                'group_id' => $group->id,
                'match_id' => $match['id'],
                'available_until' => $availableUntil,
                'points' => $template->type === 'predictive' ? 300 : 0,
                'options' => $options,
                'template_question_id' => $template->id ?? null,
            ];

            if (!isset($questionData['title']) || !isset($questionData['options'])) {
                Log::warning('Datos de pregunta incompletos:', [
                    'question_data' => $questionData,
                    'match_id' => $match['id']
                ]);
                return null;
            }

            $question = Question::firstOrCreate([
                'title' => $questionData['title'],
                'group_id' => $questionData['group_id'],
                'match_id' => $questionData['match_id'],
                'template_question_id' => $questionData['template_question_id']
            ], [
                'type' => $template->type,
                'title' => $questionData['title'],
                'description' => $questionData['description'],
                'competition_id' => $questionData['competition_id'],
                'group_id' => $questionData['group_id'],
                'match_id' => $questionData['match_id'],
                'available_until' => $availableUntil,
                'points' => $template->type === 'predictive' ? 300 : 0,
                'template_question_id' => $questionData['template_question_id']
            ]);

            if ($template->type === 'predictive' && is_array($questionData['options'])) {
                foreach ($questionData['options'] as $option) {
                    QuestionOption::updateOrCreate([
                        'question_id' => $question->id,
                        'text' => $option['text'],
                        'is_correct' => $option['is_correct']
                    ], [
                        'question_id' => $question->id,
                        'text' => $option['text'],
                        'is_correct' => $option['is_correct']
                    ]);
                }
            }

            if ($template->type === 'social') {
                $members = $group->users;
                foreach ($members as $member) {
                    QuestionOption::updateOrCreate([
                        'question_id' => $question->id,
                        'text' => $member->name,
                        'is_correct' => false
                    ], [
                        'question_id' => $question->id,
                        'text' => $member->name,
                        'is_correct' => false
                    ]);
                }
            }

            Log::info('Pregunta creada:', [
                'question_id' => $question->id,
                'match_id' => $match['id'],
                'title' => $questionText
            ]);

            return $question->load('options');
        } catch (\Exception $e) {
            Log::error('Error al crear pregunta desde template:', [
                'error' => $e->getMessage(),
                'match_id' => $match['id']
            ]);
            return null;
        }
    }
}
