<?php

namespace App\Traits;

use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Support\Facades\Cache;
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
            QuestionOption::create([
                'question_id' => $question->id,
                'text' => $user->name,
            ]);
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
}
