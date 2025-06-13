<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Competition;
use App\Models\Question;
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
use Illuminate\Support\Facades\Cache;
use App\Traits\HandlesQuestions;
use App\Services\GroupRoleService;

class GroupController extends Controller
{
    use HandlesQuestions;

    protected $footballDataService;
    protected $openAIService;
    protected $groupRoleService;
    protected $competitionMapping = [
        'champions' => 2001,  // UEFA Champions League
        'laliga' => 2014,    // La Liga
        'premier' => 2021,   // Premier League
        'world-club-championship' => 15,
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

    public function __construct(
        FootballDataService $footballDataService,
        OpenAIService $openAIService,
        GroupRoleService $groupRoleService
    ) {
        $this->footballDataService = $footballDataService;
        $this->openAIService = $openAIService;
        $this->groupRoleService = $groupRoleService;
    }

    public function index()
    {
        $groups = auth()->user()->groups()
            ->with(['creator', 'competition', 'users.roles'])
            ->get();

        $officialGroups = $groups->where('category', 'official');
        $amateurGroups = $groups->where('category', 'amateur');

        return view('groups.index', compact('officialGroups', 'amateurGroups'));
    }

    public function create()
    {
        $competitions = Competition::all();
        return view('groups.create', compact('competitions'));
    }

    public function store(Request $request)
    {
        // Verificar si ya existe un grupo con el mismo nombre creado por el mismo usuario en los últimos 5 segundos
        $recentGroup = Group::where('name', $request->name)
            ->where('created_by', auth()->id())
            ->where('created_at', '>=', now()->subSeconds(5))
            ->first();

        if ($recentGroup) {
            return redirect()->route('groups.show', $recentGroup)
                ->with('success', 'Grupo creado exitosamente.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'competition_id' => 'nullable|exists:competitions,id',
            'category' => 'required|in:official,amateur',
        ]);

        $group = Group::create([
            'name' => $request->name,
            'code' => Str::random(6),
            'created_by' => auth()->id(),
            'competition_id' => $request->competition_id,
            'category' => $request->category,
        ]);

        $group->users()->attach(auth()->id());

        return redirect()->route('groups.show', $group)
            ->with('success', 'Grupo creado exitosamente.');
    }

    protected function createPredictiveQuestion(Group $group)
    {
        try {
            $matches = FootballMatch::where(function($query) use ($group) {
                    $query->where('competition_id', $group->competition_id)
                        ->orWhere('competition_id', 4); // Mundial de Clubes
                })
                ->where('status', 'Not Started')
                ->where('date', '>=', now())
                ->where('date', '<=', now()->addDays(5))
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
        // Cache key para el grupo
        $cacheKey = "group_{$group->id}_show_data";

        // Intentar obtener datos del caché
        $cachedData = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($group) {
            // Obtener roles
            $roles = $this->groupRoleService->getGroupRoles($group);

            // Cargar relaciones del grupo
            $group->load([
                'competition',
                'users' => function ($query) use ($group) {
                    $query->with([
                        'answers' => function ($query) use ($group) {
                            $query->whereHas('question', function ($questionQuery) use ($group) {
                                $questionQuery->where('group_id', $group->id);
                            });
                        }
                    ])->withSum(['answers as total_points' => function ($query) use ($group) {
                        $query->whereHas('question', function ($questionQuery) use ($group) {
                            $questionQuery->where('group_id', $group->id);
                        });
                    }], 'points_earned');
                },
                'chatMessages.user'
            ]);

            // Asignar roles
            $this->groupRoleService->assignRolesToUsers($group, $roles);

            // Obtener preguntas y respuestas
            $matchQuestions = $this->getMatchQuestions($group, $roles);
            $socialQuestion = $this->getSocialQuestion($group, $roles);
            $userAnswers = $this->getUserAnswers($group, $matchQuestions, $socialQuestion);

            return [
                'group' => $group,
                'matchQuestions' => $matchQuestions,
                'userAnswers' => $userAnswers,
                'socialQuestion' => $socialQuestion
            ];
        });

        if (!$cachedData['group']->users->contains('id', auth()->id())) {
            return redirect()->route('dashboard')->with('error', 'No tienes acceso a este grupo.');
        }

        return view('groups.show', array_merge($cachedData, ['currentMatchday' => null]));
    }

    protected function generateMatchQuestions($matches, $group)
    {
        Log::info('Generando preguntas para partidos:', [
            'competition' => $group->competition->type,
            'total_matches' => count($matches)
        ]);

        $questions = collect();

        foreach ($matches as $match) {
            $availableUntil = null;
            try {
                if (isset($match['date']) && $match['date'] !== 'Fecha del partido') {
                    $availableUntil = Carbon::parse($match['date']);
                } else {
                    $availableUntil = now()->addDay();
                }
            } catch (\Exception $e) {
                Log::warning('Error al parsear fecha del partido, usando fecha por defecto', [
                    'date' => $match['date'],
                    'error' => $e->getMessage()
                ]);
                $availableUntil = now()->addDay();
            }

            $questionData = [
                'title' => $this->generateQuestionText($match),
                'description' => $this->generateQuestionText($match),
                'type' => 'predictive',
                'points' => 300,
                'group_id' => $group->id,
                'match_id' => $match['id'],
                'available_until' => $availableUntil,
            ];

            $question = Question::create([
                'title' => $questionData['title'],
                'description' => $questionData['description'],
                'type' => 'predictive',
                'points' => $questionData['points'],
                'group_id' => $questionData['group_id'],
                'match_id' => $questionData['match_id'],
                'available_until' => $questionData['available_until'],
            ]);

            foreach ($this->generateOptions($match) as $option) {
                QuestionOption::updateOrCreate(
                    [
                        'question_id' => $question->id,
                        'text' => $option['text']
                    ],
                    [
                        'is_correct' => $option['is_correct'] ?? false
                    ]
                );
            }

            $questions->push($question->load('options'));
        }

        return $questions;
    }

    protected function generateQuestionText($match)
    {
        $template = $this->questionTemplates[rand(0, count($this->questionTemplates) - 1)];
        return str_replace(
            ['{{home_team}}', '{{away_team}}', '{{ home_team }}', '{{ away_team }}'],
            [$match['home_team'], $match['away_team'], $match['home_team'], $match['away_team']],
            $template['template']
        );
    }

    protected function generateOptions($match)
    {
        $template = $this->questionTemplates[rand(0, count($this->questionTemplates) - 1)];
        return collect($template['options'])->map(function ($option) use ($match) {
            return [
                'text' => str_replace(
                    ['{{home_team}}', '{{away_team}}', '{{ home_team }}', '{{ away_team }}'],
                    [$match['home_team'], $match['away_team'], $match['home_team'], $match['away_team']],
                    $option
                ),
                'is_correct' => $option === $match['home_team'] || $option === $match['away_team']
            ];
        })->toArray();
    }

    public function join(Request $request)
    {
        $group = Group::where('code', $request->code)->firstOrFail();

        if ($group->users->contains(auth()->id())) {
            return redirect()->route('groups.show', $group)
                ->with('error', 'Ya eres miembro de este grupo.');
        }

        $group->users()->attach(auth()->id());

        return redirect()->route('groups.show', $group)
            ->with('success', 'Te has unido al grupo exitosamente.');
    }

    public function leave(Group $group)
    {
        // if ($group->user_id === auth()->id()) {
        //     return redirect()->route('groups.index')
        //         ->with('error', 'No puedes abandonar un grupo que has creado.');
        // }

        $group->users()->detach(auth()->id());

        return redirect()->route('groups.index')
            ->with('success', 'Has abandonado el grupo exitosamente.');
    }

    public function joinByInvite($code)
    {
        $group = Group::where('code', $code)->firstOrFail();

        if ($group->users->contains(auth()->id())) {
            return redirect()->route('groups.show', $group)
                ->with('error', 'Ya eres miembro de este grupo.');
        }

        $group->users()->attach(auth()->id());

        return redirect()->route('groups.show', $group)
            ->with('success', 'Te has unido al grupo exitosamente.');
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
            ->where('group_id', $group->id)
            ->where('available_until', '>', now())
            ->first();

        if (!$socialQuestion) {
            $socialQuestion = Question::create([
                'title' => '¿Quién será el MVP del grupo hoy?',
                'description' => 'Vota por el miembro que crees que tendrá el mejor desempeño hoy',
                'type' => 'social',
                'points' => 100,
                'group_id' => $group->id,
                'available_until' => now()->addDay(),
            ]);

            foreach ($group->users as $user) {
                QuestionOption::create([
                    'question_id' => $socialQuestion->id,
                    'text' => $user->name,
                    'is_correct' => false,
                    'user_id' => $user->id,
                ]);
            }
        } else {
            // Actualizar opciones si hay nuevos usuarios
            $existingOptions = $socialQuestion->options->pluck('text')->toArray();
            $newUsers = $group->users->filter(function($user) use ($existingOptions) {
                return !in_array($user->name, $existingOptions);
            });

            foreach ($newUsers as $user) {
                QuestionOption::create([
                    'question_id' => $socialQuestion->id,
                    'text' => $user->name,
                    'is_correct' => false,
                    'user_id' => $user->id,
                ]);
            }
        }

        return $socialQuestion->load(['options', 'answers.user']);
    }

    public function testGenerateQuestions()
    {
        $group = Group::first();
        $matches = $this->footballDataService->getMatches($group->competition->type);
        $questions = $this->generateMatchQuestions($matches, $group);
        return response()->json($questions);
    }

    protected function generateQuestionsForMatches($matches, $group)
    {
        Log::info('Generando preguntas para partidos:', [
            'competition' => $group->competition->type,
            'total_matches' => count($matches)
        ]);

        $predictiveTemplates = \App\Models\TemplateQuestion::where('type', 'predictive')
            ->whereNull('used_at')
            ->where(function ($query) use ($group) {
                $query->where('competition_id', $group->competition_id)
                    ->orWhere('competition_id', null);
            })
            ->orderBy('is_featured', 'desc')
            ->orderBy('id')
            ->take(5)
            ->get();

        $socialTemplates = \App\Models\TemplateQuestion::where('type', 'social')
            ->whereNull('used_at')
            ->orderBy('is_featured', 'desc')
            ->first();

        $questions = collect();

        // Si solo hay un partido, usaremos ese partido para todas las preguntas
        if (count($matches) === 1) {
            $match = $matches[0];
            foreach ($predictiveTemplates as $template) {
                $question = $this->createQuestionFromTemplate($template, $match, $group);
                if ($question) {
                    $questions->push($question);
                }
            }
        } else {
            usort($matches, function($a, $b) {
                $aFeatured = $a['is_featured'] ?? false;
                $bFeatured = $b['is_featured'] ?? false;

                if ($aFeatured && !$bFeatured) return -1;
                if (!$aFeatured && $bFeatured) return 1;
                return 0;
            });

            // Si hay menos de 5 partidos, generar más preguntas sobre el partido destacado
            $remainingQuestions = 5;
            $templateIndex = 0;

            foreach ($matches as $match) {
                $questionsForMatch = ($match['is_featured'] ?? false) ? min(3, $remainingQuestions) : 1;

                for ($i = 0; $i < $questionsForMatch && $templateIndex < count($predictiveTemplates); $i++) {
                    $template = $predictiveTemplates[$templateIndex++];
                    $question = $this->createQuestionFromTemplate($template, $match, $group);
                    if ($question) {
                        $questions->push($question);
                        $remainingQuestions--;
                    }
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
                ['{{home_team}}', '{{away_team}}', '{{ home_team }}', '{{ away_team }}'],
                [$match['home_team'], $match['away_team'], $match['home_team'], $match['away_team']],
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
                    ['{{home_team}}', '{{away_team}}', '{{ home_team }}', '{{ away_team }}'],
                    [$match['home_team'], $match['away_team'], $match['home_team'], $match['away_team']],
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
                    QuestionOption::updateOrCreate(
                        [
                            'question_id' => $question->id,
                            'text' => $option['text']
                        ],
                        [
                            'is_correct' => $option['is_correct'] ?? false
                        ]
                    );
                }
            }

            if ($template->type === 'social') {
                $members = $group->users;
                foreach ($members as $member) {
                    QuestionOption::updateOrCreate(
                        [
                            'question_id' => $question->id,
                            'text' => $member->name
                        ],
                        [
                            'is_correct' => false
                        ]
                    );
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
