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
use App\Models\Answer;
use App\Notifications\NewPredictiveQuestionsAvailable;
use App\Exceptions\GroupAccessException;

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
        'liga-colombia' => 121,
        'chile-campeonato-nacional' => 127,
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
        $user = auth()->user();

        $groups = $user->groups()
            ->with([
                'creator:id,name',
                'competition:id,name,type,crest_url',
                'users' => function ($query) {
                    $query->select('users.id', 'users.name')
                          ->with('roles:id,name');
                }
            ])
            ->withCount('users')
            ->get();

        // Enrich groups with additional data
        $groups = $groups->map(function($group) use ($user) {
            $group->userRank = $group->getUserRank($user->id);
            $group->pending = $group->hasPendingPredictions($user->id);
            return $group;
        });

        $officialGroups = $groups->where('category', 'official');
        $amateurGroups = $groups->where('category', 'amateur');

        // Calculate user stats
        $userStreak = $this->calculateUserStreak($user);
        $userAccuracy = $this->calculateUserAccuracy($user);
        $totalGroups = $groups->count();

        // Get featured match (next match in user's groups)
        $featuredMatch = $this->getFeaturedMatch($groups);

        // Check for pending predictions
        $hasPendingPredictions = $this->checkPendingPredictions($user, $groups);

        return view('groups.index', compact(
            'officialGroups',
            'amateurGroups',
            'userStreak',
            'userAccuracy',
            'totalGroups',
            'featuredMatch',
            'hasPendingPredictions'
        ));
    }

    public function create()
    {
        $competitions = Competition::whereIn('type', [
            'laliga',
            'premier',
            'champions',
        ])->get();
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
                ->with('success', __('controllers.groups.created_successfully'));
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'competition_id' => 'nullable|exists:competitions,id',
            'category' => 'required|in:official,amateur',
        ]);

        // Usar una transacción para asegurar la atomicidad
        return DB::transaction(function () use ($request) {
            // Verificar si ya existe un grupo idéntico
            $existingGroup = Group::where('name', $request->name)
                ->where('created_by', auth()->id())
                ->where('competition_id', $request->competition_id)
                ->where('category', $request->category)
                ->first();

            if ($existingGroup) {
                return redirect()->route('groups.show', $existingGroup)
                    ->with('success', __('controllers.groups.created_successfully'));
            }

            // Generar un código único
            do {
                $code = Str::random(6);
            } while (Group::where('code', $code)->exists());

            $group = Group::create([
                'name' => $request->name,
                'code' => $code,
                'created_by' => auth()->id(),
                'competition_id' => $request->competition_id,
                'category' => $request->category,
                'reward_or_penalty' => $request->reward_or_penalty,
            ]);

            if (!$group->users()->where('user_id', auth()->id())->exists()) {
                $group->users()->attach(auth()->id());
            }

            // Limpiar cualquier caché relacionada
            Cache::forget('user_' . auth()->id() . '_groups');
            Cache::forget('groups_list');

            return redirect()->route('groups.show', $group)
                ->with('success', __('controllers.groups.created_successfully'));
        });
    }

    protected function createPredictiveQuestion(Group $group)
    {
        try {
            $matches = FootballMatch::where('status', 'Not Started')
                ->where('date', '>=', now())
                ->where('date', '<=', now()->addDays(5))
                ->orderBy('is_featured', 'desc')
                ->orderBy('date')
                ->limit(5)
                ->get();

            if ($matches->isEmpty()) {
                Log::warning('No se encontraron partidos próximos en el calendario');
                return collect();
            }

            $matchesData = collect($matches)->map(function($match) {
                if (!isset($match['id']) || !isset($match['home_team']) || !isset($match['away_team'])) {
                    Log::warning('Partido con datos incompletos:', ['match' => $match]);
                    return null;
                }

                return [
                    'id' => $match['id'],
                    'home_team' => is_array($match['home_team']) ? $match['home_team']['name'] : $match['home_team'],
                    'away_team' => is_array($match['away_team']) ? $match['away_team']['name'] : $match['away_team'],
                    'date' => $match['date'] ?? now(),
                    'competition' => $match['competition_id'] ?? null
                ];
            })->filter()->values()->toArray();

            if (empty($matchesData)) {
                Log::warning('No hay partidos válidos después de la validación');
                return collect();
            }

            $questions = $this->generateQuestionsForMatches($matchesData, $group);

            // Enviar notificación de nuevas preguntas si se generaron
            if ($questions->count() > 0) {
                // Obtener IDs de usuarios ya notificados para evitar N+1 queries
                $notifiedUserIds = DB::table('notifications')
                    ->where('type', \App\Notifications\NewPredictiveQuestionsAvailable::class)
                    ->where('read_at', null)
                    ->whereRaw("JSON_EXTRACT(data, '$.group_id') = ?", [$group->id])
                    ->pluck('notifiable_id')
                    ->unique();

                // Notificar solo a usuarios no notificados
                $usersToNotify = $group->users->whereNotIn('id', $notifiedUserIds);

                foreach ($usersToNotify as $user) {
                    $user->notify(new NewPredictiveQuestionsAvailable($group, $questions->count()));
                }
            }

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

            // Cargar relaciones del grupo de manera optimizada
            $group->load([
                'competition:id,name,type,crest_url',
                'users' => function ($query) use ($group) {
                    $query->select('users.id', 'users.name', 'users.avatar')
                          ->with([
                              'answers' => function ($query) use ($group) {
                                  $query->select('answers.id', 'answers.user_id', 'answers.points_earned', 'answers.question_id')
                                        ->whereHas('question', function ($questionQuery) use ($group) {
                                            $questionQuery->where('group_id', $group->id);
                                        });
                              }
                          ])->withSum(['answers as total_points' => function ($query) use ($group) {
                              $query->whereHas('question', function ($questionQuery) use ($group) {
                                  $questionQuery->where('group_id', $group->id);
                              });
                          }], 'points_earned');
                },
                'chatMessages' => function ($query) {
                    $query->select('chat_messages.id', 'chat_messages.message', 'chat_messages.user_id', 'chat_messages.group_id', 'chat_messages.created_at')
                          ->with('user:id,name,avatar')
                          ->latest()
                          ->limit(50); // Limitar mensajes de chat para mejor rendimiento
                }
            ]);

            // Asignar roles
            $this->groupRoleService->assignRolesToUsers($group, $roles);

            // Obtener preguntas y respuestas
            $matchQuestions = $this->getMatchQuestions($group, $roles);
            // dd($matchQuestions);
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
            // si el grupo es el id 83 (grupo oficial de la app) AGREGAR al usuario al grupo automáticamente
            if ($group->id === 83) {
                $group->users()->attach(auth()->id());
                // Limpiar caché relacionada
                Cache::forget('user_' . auth()->id() . '_groups');
                Cache::forget('groups_list');
                // Recargar datos frescos
                $group->load([
                    'competition:id,name,type,crest_url',
                    'users' => function ($query) use ($group) {
                        $query->select('users.id', 'users.name', 'users.avatar')
                              ->with([
                                  'answers' => function ($query) use ($group) {
                                      $query->select('answers.id', 'answers.user_id', 'answers.points_earned', 'answers.question_id')
                                            ->whereHas('question', function ($questionQuery) use ($group) {
                                                $questionQuery->where('group_id', $group->id);
                                            });
                                  }
                              ])->withSum(['answers as total_points' => function ($query) use ($group) {
                                  $query->whereHas('question', function ($questionQuery) use ($group) {
                                      $questionQuery->where('group_id', $group->id);
                                  });
                              }], 'points_earned');
                    },
                    'chatMessages' => function ($query) {
                        $query->select('chat_messages.id', 'chat_messages.message', 'chat_messages.user_id', 'chat_messages.group_id', 'chat_messages.created_at')
                              ->with('user:id,name,avatar')
                              ->latest()
                              ->limit(50);
                    }
                ]);

                $this->groupRoleService->assignRolesToUsers($group, $this->groupRoleService->getGroupRoles($group));

                $matchQuestions = $this->getMatchQuestions($group, $this->groupRoleService->getGroupRoles($group));
                $socialQuestion = $this->getSocialQuestion($group, $this->groupRoleService->getGroupRoles($group));
                $userAnswers = $this->getUserAnswers($group, $matchQuestions, $socialQuestion);

                return view('groups.show', [
                    'group' => $group,
                    'matchQuestions' => $matchQuestions,
                    'userAnswers' => $userAnswers,
                    'socialQuestion' => $socialQuestion,
                    'currentMatchday' => null
                ])->with('success', __('controllers.groups.joined_official_successfully'));
            }
            throw new GroupAccessException(
                "No tienes acceso a este grupo",
                $group->id,
                auth()->id()
            );
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
        $request->validate([
            'code' => 'required|string|max:10'
        ]);

        Log::info('Solicitud de unirse a grupo', [
            'code' => $request->code,
            'user_id' => auth()->id(),
            'user_agent' => request()->userAgent(),
            'ip' => request()->ip()
        ]);

        // Buscar el grupo por código
        $group = Group::where('code', $request->code)->first();

        if (!$group) {
            Log::warning('Intento de unirse a grupo inexistente', [
                'code' => $request->code,
                'user_id' => auth()->id()
            ]);

            return redirect()->route('groups.index')
                ->with('error', __('controllers.groups.invalid_code'));
        }

        return DB::transaction(function () use ($request, $group) {

            // Verificar si ya es miembro usando la relación pivot con bloqueo
            $existingMembership = $group->users()
                ->where('user_id', auth()->id())
                ->lockForUpdate()
                ->first();

            if ($existingMembership) {
                Log::info('Usuario ya es miembro del grupo', [
                    'user_id' => auth()->id(),
                    'group_id' => $group->id,
                    'code' => $request->code
                ]);

                return redirect()->route('groups.show', $group)
                    ->with('error', __('controllers.groups.already_member'));
            }

            // Verificar si hay una solicitud reciente del mismo usuario para el mismo grupo
            $recentJoin = DB::table('group_user')
                ->where('user_id', auth()->id())
                ->where('group_id', $group->id)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->first();

            if ($recentJoin) {
                Log::info('Solicitud reciente detectada, evitando duplicado', [
                    'user_id' => auth()->id(),
                    'group_id' => $group->id,
                    'code' => $request->code
                ]);

                return redirect()->route('groups.show', $group)
                    ->with('success', __('controllers.groups.joined_successfully'));
            }

            // Agregar usuario al grupo
            $group->users()->attach(auth()->id(), [
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info('Usuario agregado exitosamente al grupo', [
                'user_id' => auth()->id(),
                'group_id' => $group->id,
                'code' => $request->code
            ]);

            // Limpiar caché relacionada
            Cache::forget('user_' . auth()->id() . '_groups');
            Cache::forget('groups_list');

            return redirect()->route('groups.show', $group)
                ->with('success', __('controllers.groups.joined_successfully'));
        });
    }

    public function leave(Group $group)
    {
        // Verificar que el usuario sea miembro del grupo
        if (!$group->users()->where('user_id', auth()->id())->exists()) {
            return redirect()->route('groups.index')
                ->with('error', __('controllers.groups.not_member'));
        }

        // Verificar que no sea el creador del grupo
        if ($group->created_by === auth()->id()) {
            return redirect()->route('groups.index')
                ->with('error', __('controllers.groups.cannot_leave_owned'));
        }

        // Remover solo al usuario actual
        $group->users()->detach(auth()->id());

        return redirect()->route('groups.index')
            ->with('success', __('controllers.groups.left_successfully'));
    }

    public function joinByInvite($code)
    {
        Log::info('Solicitud de unirse a grupo por invitación', [
            'code' => $code,
            'user_id' => auth()->id(),
            'user_agent' => request()->userAgent(),
            'ip' => request()->ip()
        ]);

        // Buscar el grupo por código
        $group = Group::where('code', $code)->first();

        if (!$group) {
            Log::warning('Intento de unirse a grupo inexistente por invitación', [
                'code' => $code,
                'user_id' => auth()->id()
            ]);

            return redirect()->route('groups.index')
                ->with('error', __('controllers.groups.invalid_invitation'));
        }

        return DB::transaction(function () use ($code, $group) {

            // Verificar si ya es miembro usando la relación pivot con bloqueo
            $existingMembership = $group->users()
                ->where('user_id', auth()->id())
                ->lockForUpdate()
                ->first();

            if ($existingMembership) {
                Log::info('Usuario ya es miembro del grupo', [
                    'user_id' => auth()->id(),
                    'group_id' => $group->id,
                    'code' => $code
                ]);

                return redirect()->route('groups.show', $group);
            }

            // Verificar si hay una solicitud reciente del mismo usuario para el mismo grupo
            $recentJoin = DB::table('group_user')
                ->where('user_id', auth()->id())
                ->where('group_id', $group->id)
                ->where('created_at', '>=', now()->subSeconds(5))
                ->first();

            if ($recentJoin) {
                Log::info('Solicitud reciente detectada, evitando duplicado', [
                    'user_id' => auth()->id(),
                    'group_id' => $group->id,
                    'code' => $code
                ]);

                return redirect()->route('groups.show', $group)
                    ->with('success', __('controllers.groups.joined_successfully'));
            }

            // Agregar usuario al grupo
            $group->users()->attach(auth()->id(), [
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info('Usuario agregado exitosamente al grupo', [
                'user_id' => auth()->id(),
                'group_id' => $group->id,
                'code' => $code
            ]);

            // Limpiar caché relacionada
            Cache::forget('user_' . auth()->id() . '_groups');
            Cache::forget('groups_list');
            Cache::forget("group_{$group->id}_show_data");

            return redirect()->route('groups.show', $group)
                ->with('success', __('controllers.groups.joined_successfully'));
        });
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

        $socialQuestion = Question::where('type', 'social')->where('group_id', $group->id)->where('available_until', '>', now())->first();

        $question = null;

        if (!$socialQuestion) {
            // Obtener una pregunta social aleatoria que no haya sido usada en este grupo
            $socialQuestion = \App\Models\TemplateQuestion::where('type', 'social')
            ->where(function ($query) use ($group) {
                $query->whereNull('used_at')
                    ->orWhereNotExists(function ($subquery) use ($group) {
                        $subquery->select(DB::raw(1))
                            ->from('questions')
                            ->whereColumn('questions.template_question_id', 'template_questions.id')
                            ->where('questions.group_id', $group->id);
                    });
            })
            ->inRandomOrder()
            ->first();

            // crear la pregunta
            $question = Question::create([
                'title' => $socialQuestion->text,
                'description' => $socialQuestion->text,
                'type' => 'social',
                'points' => 100,
                'group_id' => $group->id,
                'available_until' => now()->addDay(),
                'template_question_id' => $socialQuestion->id,
            ]);

            foreach ($group->users as $user) {
                $questionOption = QuestionOption::create([
                    'question_id' => $question->id,
                    'text' => $user->name,
                    'is_correct' => false,
                    'user_id' => $user->id,
                ]);
            }
        } else {
            if ($socialQuestion->id == 42 || $socialQuestion->id == 340) {
                Log::info('Pregunta social 42: ' . $socialQuestion->text);
                return $socialQuestion->load(['options', 'answers.user']);
            }
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

        return $question?->load(['options', 'answers.user']);
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

    /**
     * Actualiza el campo reward_or_penalty del grupo
     */
    public function updateRewardOrPenalty(Request $request, Group $group)
    {
        $request->validate([
            'reward_or_penalty' => 'required|string|max:1000',
        ]);

        $group->reward_or_penalty = $request->reward_or_penalty;
        $group->save();

        // Limpiar caché relevante
        Cache::forget("group_{$group->id}_show_data");

        return response()->json([
            'success' => true,
            'reward_or_penalty' => $group->reward_or_penalty
        ]);
    }

    /**
     * Muestra los resultados de las últimas respuestas predictivas del usuario en el grupo
     */
    public function showPredictiveResults(Group $group)
    {
        // Verificar que el usuario sea miembro del grupo
        if (!$group->users->contains('id', auth()->id())) {
            throw new GroupAccessException(
                "No tienes acceso a este grupo",
                $group->id,
                auth()->id()
            );
        }

        // Obtener las últimas respuestas predictivas del usuario en este grupo con una sola query optimizada
        $predictiveAnswers = Answer::where('user_id', auth()->id())
            ->whereHas('question', function ($query) use ($group) {
                $query->where('group_id', $group->id)
                    ->where('type', 'predictive')
                    ->whereNotNull('result_verified_at'); // Solo preguntas con resultados verificados
            })
            ->with([
                'question' => function ($query) {
                    $query->with(['football_match', 'options']);
                },
                'questionOption'
            ])
            ->orderBy('created_at', 'desc')
            ->limit(20) // Últimas 20 respuestas
            ->get();

        // Obtener los IDs de las preguntas
        $questionIds = $predictiveAnswers->pluck('question_id')->unique();

        // Obtener todos los votos de todos los usuarios del grupo para esas preguntas en una sola query
        $allVotes = Answer::whereIn('question_id', $questionIds)
            ->whereHas('question', function ($query) use ($group) {
                $query->where('group_id', $group->id)
                    ->where('type', 'predictive')
                    ->whereNotNull('result_verified_at');
            })
            ->with([
                'user:id,name', // Solo cargar campos necesarios
                'questionOption:id,text'
            ])
            ->get()
            ->groupBy('question_id');

        // Agrupar por fecha para mejor organización
        $groupedAnswers = $predictiveAnswers->groupBy(function ($answer) {
            return $answer->question->football_match ?
                $answer->question->football_match->date->format('Y-m-d') :
                $answer->created_at->format('Y-m-d');
        });

        // Calcular estadísticas
        $stats = [
            'total_answers' => $predictiveAnswers->count(),
            'correct_answers' => $predictiveAnswers->where('is_correct', true)->count(),
            'total_points' => $predictiveAnswers->sum('points_earned'),
            'accuracy_percentage' => $predictiveAnswers->count() > 0 ?
                round(($predictiveAnswers->where('is_correct', true)->count() / $predictiveAnswers->count()) * 100, 1) : 0
        ];

        // Pasar $allVotes a la vista
        return view('groups.predictive-results', compact('group', 'groupedAnswers', 'stats', 'allVotes'));
    }

    /**
     * Calculate user's current streak of consecutive days with predictions
     */
    protected function calculateUserStreak($user)
    {
        $answers = Answer::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($answers->isEmpty()) {
            return 0;
        }

        $streak = 1;
        $lastDate = $answers->first()->created_at->startOfDay();

        foreach ($answers->skip(1) as $answer) {
            $answerDate = $answer->created_at->startOfDay();
            $dayDiff = $lastDate->diffInDays($answerDate);

            if ($dayDiff === 1) {
                $streak++;
                $lastDate = $answerDate;
            } elseif ($dayDiff > 1) {
                break;
            }
        }

        return $streak;
    }

    /**
     * Calculate user's prediction accuracy percentage
     */
    protected function calculateUserAccuracy($user)
    {
        $totalAnswers = Answer::where('user_id', $user->id)
            ->whereHas('question', function($q) {
                $q->whereNotNull('result_verified_at');
            })
            ->count();

        if ($totalAnswers === 0) {
            return 0;
        }

        $correctAnswers = Answer::where('user_id', $user->id)
            ->where('is_correct', true)
            ->whereHas('question', function($q) {
                $q->whereNotNull('result_verified_at');
            })
            ->count();

        return round(($correctAnswers / $totalAnswers) * 100);
    }

    /**
     * Get the next featured match from user's groups
     */
    protected function getFeaturedMatch($groups)
    {
        $competitionIds = $groups->pluck('competition_id')->filter()->unique();

        if ($competitionIds->isEmpty()) {
            return null;
        }

        return FootballMatch::whereIn('competition_id', $competitionIds)
            ->where('date', '>', now())
            ->with(['homeTeam', 'awayTeam', 'competition'])
            ->orderBy('date', 'asc')
            ->first();
    }

    /**
     * Check if user has pending predictions in any group
     */
    protected function checkPendingPredictions($user, $groups)
    {
        $groupIds = $groups->pluck('id');

        $unansweredQuestions = Question::whereIn('group_id', $groupIds)
            ->where('available_until', '>', now())
            ->whereDoesntHave('answers', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->exists();

        return $unansweredQuestions;
    }

    /**
     * Get group ranking data for API
     */
    public function getRanking(Group $group)
    {
        // Verify user has access
        if (!$group->users->contains('id', auth()->id())) {
            return response()->json([
                'error' => 'No tienes acceso a este grupo'
            ], 403);
        }

        // Get ranked users with their stats
        $rankedUsers = $group->users()
            ->withCount([
                'answers as correct_answers' => function($q) use ($group) {
                    $q->where('is_correct', true)
                      ->whereHas('question', function($query) use ($group) {
                          $query->where('group_id', $group->id);
                      });
                }
            ])
            ->orderBy('total_points', 'desc')
            ->get()
            ->map(function($user, $index) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'total_points' => $user->total_points ?? 0,
                    'correct_answers' => $user->correct_answers ?? 0,
                    'rank' => $index + 1,
                    'is_current_user' => $user->id === auth()->id()
                ];
            });

        // Get current user stats
        $currentUser = $rankedUsers->firstWhere('is_current_user', true);

        return response()->json([
            'players' => $rankedUsers->values(),
            'stats' => [
                'total_players' => $rankedUsers->count(),
                'user_position' => $currentUser['rank'] ?? null,
                'user_points' => $currentUser['total_points'] ?? 0
            ]
        ]);
    }

    function getGroupsByMatch($matchId)
    {
        $match = FootballMatch::findOrFail($matchId);

        $groups = Group::where('competition_id', $match->competition_id)
            ->with(['users', 'competition'])
            // and auth user is member
            ->whereHas('users', function($q) {
                $q->where('user_id', auth()->id());
            })
            ->get();

        return response()->json([
            'match' => $match,
            'groups' => $groups
        ]);
    }
}
