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

        // Si el grupo tiene una competición asociada, generamos una pregunta predictiva
        // if ($group->competition) {
        //     $this->createPredictiveQuestion($group);
        // }

        return redirect()->route('groups.show', $group)
            ->with('success', 'Grupo creado exitosamente.');
    }

    protected function createPredictiveQuestion(Group $group)
    {
        try {
            // Obtener los próximos partidos de la base de datos
            $matches = FootballMatch::where('league', $group->competition->type)
                ->where('status', 'Not Started')
                // dentro de los próximos 7 días
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
                // Validar que el partido tenga los datos necesarios
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
        // Verificar que el usuario pertenece al grupo
        if (!$group->users()->where('user_id', auth()->id())->exists()) {
            return redirect()->route('dashboard')->with('error', 'No tienes acceso a este grupo.');
        }

        // Obtener una pregunta social aleatoria que esté disponible por 24 horas
        $socialQuestion = Question::where('type', 'social')
            ->where('group_id', $group->id)
            ->where('available_until', '>', now())
            // ->where('available_until', '<', now()->addDay())
            ->with(['answers.user', 'options'])
            ->inRandomOrder()
            ->first();

        // si el grupo tiene solo 2 miembros o menos, volver a generar las opciones
        if ($group->users()->count() <= 3 && $socialQuestion) {
            // $socialQuestion->options()->delete();
            foreach ($group->users()->get() as $user) {
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
            // Obtener las preguntas de los partidos
            $matchQuestions = Question::where('type', 'predictive')
                ->where('group_id', $group->id)
                // ->where('available_until', '>', now())
                ->with(['options', 'answers.user', 'answers.option'])
                ->get();

            // Si no hay preguntas, generar nuevas
            if ($matchQuestions->isEmpty()) {
                $createdQuestions = $this->createPredictiveQuestion($group);
                if ($createdQuestions) {
                    $matchQuestions = $matchQuestions->merge($createdQuestions);
                }
                $socialQuestion = $createdQuestions->where('type', 'social')->first();
            }


        }

        // Asegurar que no hay duplicados y limitar a 5 preguntas
        $matchQuestions = $matchQuestions->unique('id')->take(5);
        Log::info('Total de preguntas finales:', ['count' => $matchQuestions->count()]);

        // Verificar respuestas del usuario
        $userAnswers = auth()->user()->answers()
            ->whereIn('question_id', $matchQuestions->pluck('id'))
            ->when($socialQuestion, function ($query) use ($socialQuestion) {
                $query->orWhere('question_id', $socialQuestion->id);
            })
            ->pluck('option_id', 'question_id');

        // Marcar preguntas como disabled según el estado del partido
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

        // Verificar que tenemos los datos necesarios del partido
        if (!isset($match['id']) || !isset($match['home_team']) || !isset($match['away_team'])) {
            Log::error('Datos de partido incompletos:', ['match' => $match]);
            return $questions;
        }

        // Preparar datos del partido para OpenAI
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

                // Asegurarnos de que la fecha sea válida
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
        // Cargar los usuarios del grupo
        $group->load('users');

        // Verificar que el grupo tenga más de un miembro
        if ($group->users->count() < 2) {
            return null;
        }

        // Buscar una pregunta social que no haya sido respondida por ningún usuario del grupo
        $socialQuestion = Question::where('type', 'social')
            // ->whereDoesntHave('answers', function($query) use ($group) {
            //     $query->whereIn('user_id', $group->users->pluck('id'));
            // })
            ->latest()
            ->first();

        if (!$socialQuestion) {
            return null;
        }

        // Crear una copia de la pregunta específica para este grupo
        $groupQuestion = Question::create([
            'title' => $socialQuestion->title,
            'description' => $socialQuestion->description,
            'type' => 'social',
            'points' => 0, // Las preguntas sociales no suman puntos
            'group_id' => $group->id,
            'available_until' => now()->addDay(),
        ]);

        // Crear una opción por cada usuario del grupo
        foreach ($group->users as $user) {
            Option::create([
                'question_id' => $groupQuestion->id,
                'text' => $user->name,
                'is_correct' => false,
                'user_id' => $user->id,
            ]);
        }

        // Cargar las relaciones necesarias
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
        
        // Obtener las plantillas disponibles
        $predictiveTemplates = \App\Models\TemplateQuestion::where('type', 'predictive')
            ->orderBy('is_featured', 'desc')
            ->orderBy('id')
            ->take(count($matches)) // Solo necesitamos tantas plantillas como partidos
            ->get();
            
        $socialTemplates = \App\Models\TemplateQuestion::where('type', 'social')
            ->orderBy('is_featured', 'desc')
            ->first(); // Solo necesitamos una plantilla social
        
        $questions = collect();
        
        // Mezclar los partidos para aleatoriedad, pero mantener los destacados primero
        usort($matches, function($a, $b) {
            $aFeatured = $a['is_featured'] ?? false;
            $bFeatured = $b['is_featured'] ?? false;
            
            if ($aFeatured && !$bFeatured) return -1;
            if (!$aFeatured && $bFeatured) return 1;
            return 0;
        });
        
        // Asignar una plantilla única a cada partido
        foreach ($matches as $index => $match) {
            if (isset($predictiveTemplates[$index])) {
                $template = $predictiveTemplates[$index];
                $question = $this->createQuestionFromTemplate($template, $match, $group);
                if ($question) {
                    $questions->push($question);
                }
            }
        }
        
        // Agregar una pregunta social si hay plantillas sociales disponibles
        if ($socialTemplates && count($matches) > 0) {
            // Usar el primer partido para la pregunta social
            $socialQuestion = $this->createQuestionFromTemplate($socialTemplates, $matches[0], $group);
            if ($socialQuestion) {
                $questions->push($socialQuestion);
            }
        }
        // Obtener una pregunta social aleatoria
        // if ($socialTemplates->count() > 0) {
        //     $socialTemplate = $socialTemplates->random(1);
        //     // unset this template from the collection
        //     $question = $this->createQuestionFromTemplate($socialTemplate, $match, $group);
        //     if ($question) {
        //         $questions->push($question);
        //     }
        // }

        return $questions;
    }

    protected function createQuestionFromTemplate($template, $match, $group)
    {
        try {
            // Verificar que el template sea un objeto
            if (!is_object($template)) {
                Log::warning('Template no es un objeto:', [
                    'template' => $template,
                    'match_id' => $match['id']
                ]);
                return null;
            }

            // Verificar que el template tenga los campos necesarios
            if (!isset($template->text) || !isset($template->type) || !isset($template->options)) {
                Log::warning('Template incompleto:', [
                    'template' => $template,
                    'match_id' => $match['id']
                ]);
                return null;
            }

            // Reemplazar placeholders en la pregunta y opciones
            $questionText = str_replace(
                ['{{home_team}}', '{{away_team}}'],
                [$match['home_team'], $match['away_team']],
                $template->text
            );

            // Reemplazar nombres de equipos en las opciones
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

            // Si es una pregunta sobre jugadores, obtener la plantilla de ambos equipos
            if (strpos($questionText, 'jugador') !== false) {
                try {
                    $homeTeamPlayers = $this->footballDataService->getTeamPlayers($match['{{home_team}}']);
                    $awayTeamPlayers = $this->footballDataService->getTeamPlayers($match['{{away_team}}']);
                    
                    // Crear opciones con nombres de jugadores aleatorios
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
                    
                    // Mezclar las opciones para que no siempre estén en el mismo orden
                    shuffle($options);
                } catch (\Exception $e) {
                    Log::error('Error al obtener jugadores:', [
                        'error' => $e->getMessage(),
                        'match' => $match
                    ]);
                    return null;
                }
            }
            // dump($competition);

            // Crear la pregunta con las opciones
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

            // Validar que la pregunta tenga todos los datos necesarios
            if (!isset($questionData['title']) || !isset($questionData['options'])) {
                Log::warning('Datos de pregunta incompletos:', [
                    'question_data' => $questionData,
                    'match_id' => $match['id']
                ]);
                return null;
            }

            // Crear la pregunta
            // dump($questionData);
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
                // Crear las opciones
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
                // set options with each name of group members
                $members = $group->users;
                // dd($members);
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
                // 'template_id' => $template->id ?? null,
                'match_id' => $match['id']
            ]);
            return null;
        }
    }    
}
