<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\QuestionEvaluationService;
use App\Models\Question;
use App\Models\FootballMatch;
use App\Models\QuestionOption;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QuestionEvaluationServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuestionEvaluationService $service;
    private FootballMatch $match;
    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuestionEvaluationService();

        $this->group = Group::factory()->create();

        $this->match = FootballMatch::create([
            'external_id' => 9999,
            'home_team' => 'Arsenal',
            'away_team' => 'Liverpool',
            'date' => now()->subHours(2),
            'status' => 'FINISHED',
            'home_team_score' => 2,
            'away_team_score' => 1,
            'league' => 'PL',
            'events' => json_encode([
                ['type' => 'GOAL', 'team' => 'HOME', 'minute' => 15, 'player' => 'Saka'],
                ['type' => 'GOAL', 'team' => 'AWAY', 'minute' => 35, 'player' => 'Salah'],
                ['type' => 'GOAL', 'team' => 'HOME', 'minute' => 70, 'player' => 'Odegaard']
            ]),
            'statistics' => json_encode([
                'home' => ['possession' => 65, 'passes' => 500, 'shots' => 10],
                'away' => ['possession' => 35, 'passes' => 300, 'shots' => 4]
            ])
        ]);
    }

    public function test_evaluate_winner_home_win(): void
    {
        $question = Question::create([
            'title' => '¿Cuál será el resultado?',
            'type' => 'resultado',
            'match_id' => $this->match->id,
            'group_id' => $this->group->id,
            'points' => 300,
            'available_until' => now()->addHours(24)
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Victoria Arsenal', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'Victoria Liverpool', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'Empate', 'is_correct' => false]);

        $question->refresh();
        $correctOptions = $this->service->evaluateQuestion($question, $this->match);

        $this->assertCount(1, $correctOptions);
        $this->assertContains($question->options[0]->id, $correctOptions);
    }

    public function test_evaluate_first_goal(): void
    {
        $question = Question::create([
            'title' => '¿Quién anotará el primer gol?',
            'type' => 'primer_gol',
            'match_id' => $this->match->id,
            'group_id' => $this->group->id,
            'points' => 300,
            'available_until' => now()->addHours(24)
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Arsenal', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'Liverpool', 'is_correct' => false]);

        $question->refresh();
        $correctOptions = $this->service->evaluateQuestion($question, $this->match);

        $this->assertCount(1, $correctOptions);
        $this->assertContains($question->options[0]->id, $correctOptions);
    }

    public function test_evaluate_both_score(): void
    {
        $question = Question::create([
            'title' => '¿Ambos equipos anotan?',
            'type' => 'ambos_anotan',
            'match_id' => $this->match->id,
            'group_id' => $this->group->id,
            'points' => 300,
            'available_until' => now()->addHours(24)
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Sí', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'No', 'is_correct' => false]);

        $question->refresh();
        $correctOptions = $this->service->evaluateQuestion($question, $this->match);

        $this->assertCount(1, $correctOptions);
        $this->assertContains($question->options[0]->id, $correctOptions);
    }

    public function test_evaluate_exact_score(): void
    {
        $question = Question::create([
            'title' => '¿Cuál será el marcador exacto?',
            'type' => 'marcador_exacto',
            'match_id' => $this->match->id,
            'group_id' => $this->group->id,
            'points' => 300,
            'available_until' => now()->addHours(24)
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => '2-1', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => '1-1', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => '3-1', 'is_correct' => false]);

        $question->refresh();
        $correctOptions = $this->service->evaluateQuestion($question, $this->match);

        $this->assertCount(1, $correctOptions);
        $this->assertContains($question->options[0]->id, $correctOptions);
    }

    public function test_evaluate_yellow_cards(): void
    {
        $matchWithCards = FootballMatch::create([
            'external_id' => 10000,
            'home_team' => 'Team A',
            'away_team' => 'Team B',
            'date' => now()->subHours(2),
            'status' => 'FINISHED',
            'home_team_score' => 1,
            'away_team_score' => 0,
            'league' => 'PL',
            'events' => json_encode([
                ['type' => 'GOAL', 'team' => 'HOME', 'minute' => 15],
                ['type' => 'CARD', 'detail' => 'YELLOW', 'team' => 'AWAY', 'minute' => 30],
                ['type' => 'CARD', 'detail' => 'YELLOW', 'team' => 'AWAY', 'minute' => 45],
                ['type' => 'CARD', 'detail' => 'YELLOW', 'team' => 'HOME', 'minute' => 60]
            ])
        ]);

        $question = Question::create([
            'title' => '¿Más de 2 tarjetas amarillas?',
            'type' => 'tarjetas_amarillas',
            'match_id' => $matchWithCards->id,
            'group_id' => $this->group->id,
            'points' => 300,
            'available_until' => now()->addHours(24)
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Sí', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'No', 'is_correct' => false]);

        $question->refresh();
        $correctOptions = $this->service->evaluateQuestion($question, $matchWithCards);

        $this->assertCount(1, $correctOptions);
    }

    public function test_evaluate_goals_over_under(): void
    {
        $question = Question::create([
            'title' => '¿Más de 2.5 goles?',
            'type' => 'goles_over',
            'match_id' => $this->match->id,
            'group_id' => $this->group->id,
            'points' => 300,
            'available_until' => now()->addHours(24)
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Sí (Over)', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'No (Under)', 'is_correct' => false]);

        $question->refresh();
        $correctOptions = $this->service->evaluateQuestion($question, $this->match);

        $this->assertCount(1, $correctOptions);
        $this->assertContains($question->options[0]->id, $correctOptions);
    }

    public function test_evaluate_possession(): void
    {
        $question = Question::create([
            'title' => '¿Arsenal tendrá más del 60% de posesión?',
            'type' => 'posesion',
            'match_id' => $this->match->id,
            'group_id' => $this->group->id,
            'points' => 300,
            'available_until' => now()->addHours(24)
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Sí', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'No', 'is_correct' => false]);

        $question->refresh();
        $correctOptions = $this->service->evaluateQuestion($question, $this->match);

        $this->assertCount(1, $correctOptions);
        $this->assertContains($question->options[0]->id, $correctOptions);
    }

    public function test_evaluate_own_goal(): void
    {
        $matchWithOwnGoal = FootballMatch::create([
            'external_id' => 10001,
            'home_team' => 'Team A',
            'away_team' => 'Team B',
            'date' => now()->subHours(2),
            'status' => 'FINISHED',
            'home_team_score' => 1,
            'away_team_score' => 0,
            'league' => 'PL',
            'events' => json_encode([
                ['type' => 'GOAL', 'team' => 'AWAY', 'minute' => 15],
                ['type' => 'OWN_GOAL', 'team' => 'AWAY', 'minute' => 30]
            ])
        ]);

        $question = Question::create([
            'title' => '¿Habrá un autogol?',
            'type' => 'autogol',
            'match_id' => $matchWithOwnGoal->id,
            'group_id' => $this->group->id,
            'points' => 300,
            'available_until' => now()->addHours(24)
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Sí', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'No', 'is_correct' => false]);

        $question->refresh();
        $correctOptions = $this->service->evaluateQuestion($question, $matchWithOwnGoal);

        $this->assertCount(1, $correctOptions);
        $this->assertContains($question->options[0]->id, $correctOptions);
    }

    public function test_no_correct_option_when_no_match(): void
    {
        $question = Question::create([
            'title' => '¿Habrá un penal?',
            'type' => 'penalti',
            'match_id' => $this->match->id,
            'group_id' => $this->group->id,
            'points' => 300,
            'available_until' => now()->addHours(24)
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Sí', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'No', 'is_correct' => false]);

        $question->refresh();
        $correctOptions = $this->service->evaluateQuestion($question, $this->match);

        $this->assertCount(1, $correctOptions);
        $this->assertContains($question->options[1]->id, $correctOptions);
    }
}
