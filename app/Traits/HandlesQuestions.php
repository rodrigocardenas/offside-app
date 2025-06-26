<?php

namespace App\Traits;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\TemplateQuestion;

trait HandlesQuestions
{
    protected function getMatchQuestions($group, $roles)
    {
        $matchQuestionsCacheKey = "group_{$group->id}_match_questions";
        return Cache::remember($matchQuestionsCacheKey, now()->addMinutes(5), function () use ($group, $roles) {
            if (!$group->competition) {
                return collect();
            }

            // Verificar si todos los partidos de la jornada actual han terminado
            $allMatchesFinished = $this->checkIfAllMatchesFinished($group);

            $questions = $this->getExistingQuestions($group);

            if ($allMatchesFinished || $questions->isEmpty()) {
                $createdQuestions = $this->createPredictiveQuestion($group);
                if ($createdQuestions) {
                    $questions = $questions->merge($createdQuestions);
                }
            }

            return $this->processQuestions($questions);
        });
    }

    protected function getSocialQuestion($group, $roles)
    {
        $socialQuestionCacheKey = "group_{$group->id}_social_question";
        return Cache::remember($socialQuestionCacheKey, now()->addMinutes(5), function () use ($group, $roles) {
            $question = Question::where('type', 'social')
                ->where('group_id', $group->id)
                ->where('available_until', '>', now())
                ->with([
                    'answers.user',
                    'answers.questionOption',
                    'options',
                    'templateQuestion' => function ($query) {
                        $query->with([
                            'userReactions' => function ($query) {
                                $query->where('user_id', auth()->id());
                            }
                        ]);
                    }
                ])
                ->first();

            if ($question) {
                $this->updateSocialQuestionOptions($question, $group);
            } else {
                $question = $this->createSocialQuestion($group);
            }

            return $question;
        });
    }

    private function createSocialQuestion($group) : ?Question
    {
        // create a question with type social using template question
        $templateQuestion = TemplateQuestion::query()
            ->where('type', 'social')
            ->whereNull('used_at')
            ->orderBy('is_featured', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$templateQuestion) {
            return null;
        }

        $question = Question::create([
            'type' => 'social',
            'group_id' => $group->id,
            'template_question_id' => $templateQuestion->id,
            'available_until' => now()->addHours(24),
            'title' => $templateQuestion->text,
        ]);
        // set options
        foreach ($group->users as $user) {
            // QuestionOption::create([
            //     'question_id' => $question->id,
            //     'text' => $user->name,
            // ]);
        }

        return $question;
    }

    protected function getUserAnswers($group, $matchQuestions, $socialQuestion)
    {
        $userAnswersCacheKey = "user_{$group->id}_answers";
        return Cache::remember($userAnswersCacheKey, now()->addMinutes(5), function () use ($group, $matchQuestions, $socialQuestion) {
            return auth()->user()->answers()
                ->whereIn('question_id', $matchQuestions->pluck('id'))
                ->when($socialQuestion, function ($query) use ($socialQuestion) {
                    $query->orWhere('question_id', $socialQuestion->id);
                })
                ->with(['questionOption', 'question'])
                ->get(['question_option_id', 'question_id', 'updated_at']);
        });
    }

    private function checkIfAllMatchesFinished($group)
    {
        return \App\Models\FootballMatch::where(function($query) use ($group) {
                $query->where('league', $group->competition->type)
                    ->orWhere('league', 'world-club-championship')
                    ->orWhere('competition_id', 4); // Mundial de Clubes
            })
            ->where('date', '>=', now()->subDays(5))
            ->where('date', '<=', now())
            ->where('status', '!=', 'FINISHED')
            ->doesntExist();
    }

    private function getExistingQuestions($group)
    {
        return Question::where('type', 'predictive')
            ->where('group_id', $group->id)
            ->where('available_until', '>', now()->subHours(4))
            ->with([
                'options',
                'answers.user',
                'answers.questionOption',
                'football_match',
                'templateQuestion' => function ($query) {
                    $query->with([
                        'userReactions' => function ($query) {
                            $query->where('user_id', auth()->id());
                        }
                    ]);
                }
            ])
            ->get();
    }

    private function processQuestions($questions)
    {
        $questions->each(function ($question) {
            if ($question->football_match) {
                $question->is_disabled = $question->football_match->status !== 'Not Started';
            } else {
                $question->is_disabled = $question->available_until->isPast();
            }

            $this->setQuestionModificationStatus($question);
        });

        return $questions->unique('id')->take(5);
    }

    private function setQuestionModificationStatus($question)
    {
        $userAnswer = $question->answers->where('user_id', auth()->id())->first();
        if ($userAnswer) {
            $question->can_modify = $question->football_match
                ? $question->football_match->status === 'Not Started' && $userAnswer->created_at->diffInMinutes(now()) <= 5
                : $question->available_until->isFuture() && $userAnswer->created_at->diffInMinutes(now()) <= 5;
        } else {
            $question->can_modify = $question->football_match
                ? $question->football_match->status === 'Not Started'
                : $question->available_until->isFuture();
        }
    }

    private function updateSocialQuestionOptions($question, $group)
    {
        if ($group->users->count() <= 4) {
            foreach ($group->users as $user) {
                QuestionOption::updateOrCreate([
                    'question_id' => $question->id,
                    'text' => $user->name,
                ], [
                    'is_correct' => true
                ]);
            }
            $question->refresh();
            Cache::forget("group_{$group->id}_social_question");
        }
    }

    /**
     * Rellena las preguntas predictivas de un grupo hasta tener 5 vigentes.
     * Si no hay suficientes partidos, repite con el partido destacado.
     * Solo crea preguntas si hay menos de 5 vigentes.
     */
    public function fillGroupPredictiveQuestions($group)
    {
        // 1. Obtener preguntas predictivas vigentes
        $vigentes = \App\Models\Question::where('type', 'predictive')
            ->where('group_id', $group->id)
            ->where('available_until', '>', now())
            ->get();

        $faltantes = 5 - $vigentes->count();
        if ($faltantes <= 0) {
            return $vigentes;
        }

        // 2. Buscar partidos próximos de la competición del grupo
        $matches = \App\Models\FootballMatch::where(function($q) use ($group) {
                $q->where('competition_id', $group->competition_id ?? 4)
                  ->orWhere('competition_id', 4);
            })
            ->where('status', 'Not Started')
            ->where('date', '>=', now())
            ->orderBy('date')
            ->get();

        // 3. Filtrar partidos que ya tengan pregunta vigente en el grupo
        $matchesSinPregunta = $matches->filter(function($match) use ($group) {
            return !\App\Models\Question::where('type', 'predictive')
                ->where('group_id', $group->id)
                ->where('match_id', $match->id)
                ->where('available_until', '>', now())
                ->exists();
        });

        // 4. Obtener plantillas predictivas
        $plantillas = \App\Models\TemplateQuestion::where('type', 'predictive')
            ->orderBy('is_featured', 'desc')
            ->orderBy('id')
            ->get();
        $plantillaIndex = 0;

        $nuevas = collect();
        // 5. Crear preguntas para partidos próximos sin pregunta
        foreach ($matchesSinPregunta as $match) {
            if ($faltantes <= 0) break;
            $plantilla = $plantillas[$plantillaIndex % $plantillas->count()];
            $plantillaIndex++;
            $pregunta = $this->createQuestionFromTemplate($plantilla, $match, $group);
            if ($pregunta) {
                $nuevas->push($pregunta);
                $faltantes--;
            }
        }

        // 6. Si aún faltan, usar el partido destacado
        if ($faltantes > 0) {
            $destacado = $matches->where('is_featured', true)->sortBy('date')->first();
            if ($destacado) {
                while ($faltantes > 0) {
                    $plantilla = $plantillas[$plantillaIndex % $plantillas->count()];
                    $plantillaIndex++;
                    $pregunta = $this->createQuestionFromTemplate($plantilla, $destacado, $group);
                    if ($pregunta) {
                        $nuevas->push($pregunta);
                        $faltantes--;
                    } else {
                        break;
                    }
                }
            }
        }

        return $vigentes->merge($nuevas)->take(5);
    }

    /**
     * Crea una pregunta desde una plantilla para un partido específico
     */
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

            // Para preguntas sobre jugadores, usar opciones genéricas ya que no tenemos acceso al FootballDataService
            if (strpos($questionText, 'jugador') !== false) {
                $options = [
                    ['text' => 'Jugador Local 1', 'is_correct' => false],
                    ['text' => 'Jugador Local 2', 'is_correct' => false],
                    ['text' => 'Jugador Visitante 1', 'is_correct' => false],
                    ['text' => 'Jugador Visitante 2', 'is_correct' => false]
                ];
                shuffle($options);
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
