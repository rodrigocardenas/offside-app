<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\QuestionEvaluationService;
use App\Services\GeminiService;
use App\Models\Question;
use App\Models\FootballMatch;
use App\Models\QuestionOption;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class QuestionEvaluationServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuestionEvaluationService $service;
    private FootballMatch $match;
    private Group $group;
    private \Mockery\MockInterface $geminiMock;

    protected function setUp(): void
    {
        parent::setUp();
        config(['question_evaluation.gemini_fallback_enabled' => false]);

        $this->geminiMock = Mockery::mock(GeminiService::class);
        $this->app->instance(GeminiService::class, $this->geminiMock);

        $this->service = app(QuestionEvaluationService::class);

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
            'statistics' => $this->verifiedStatistics()
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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

    public function test_evaluate_goal_before_minute_affirmative(): void
    {
        $question = Question::create([
            'title' => '¿Habrá gol antes de los primeros 15 minutos?',
            'type' => 'gol_15',
            'match_id' => $this->match->id,
            'group_id' => $this->group->id,
            'points' => 300,
            'available_until' => now()->addHours(24)
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Sí, habrá gol', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'No, no habrá gol', 'is_correct' => false]);

        $question->refresh();
        $correctOptions = $this->service->evaluateQuestion($question, $this->match);

        $this->assertCount(1, $correctOptions);
        $this->assertContains($question->options[0]->id, $correctOptions);
    }

    public function test_evaluate_goal_before_minute_negative(): void
    {
        $lateGoalMatch = FootballMatch::create([
            'external_id' => 20002,
            'home_team' => 'Roma',
            'away_team' => 'Milan',
            'date' => now()->subHours(2),
            'status' => 'FINISHED',
            'home_team_score' => 1,
            'away_team_score' => 0,
            'league' => 'Serie A',
            'events' => json_encode([
                ['type' => 'GOAL', 'team' => 'HOME', 'minute' => 28]
            ]),
            'statistics' => $this->verifiedStatistics()
        ]);

        $question = Question::create([
            'title' => '¿Se anotará gol antes del minuto 10?',
            'type' => 'gol_10',
            'match_id' => $lateGoalMatch->id,
            'group_id' => $this->group->id,
            'points' => 300,
            'available_until' => now()->addHours(24)
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Sí, habrá gol', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'No, no habrá gol', 'is_correct' => false]);

        $question->refresh();
        $correctOptions = $this->service->evaluateQuestion($question, $lateGoalMatch);

        $this->assertCount(1, $correctOptions);
        $this->assertContains($question->options[1]->id, $correctOptions);
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
                ['type' => 'CARD', 'card' => 'YELLOW', 'team' => 'AWAY', 'minute' => 30],
                ['type' => 'CARD', 'card' => 'YELLOW', 'team' => 'AWAY', 'minute' => 45],
                ['type' => 'CARD', 'card' => 'YELLOW', 'team' => 'HOME', 'minute' => 60]
            ]),
            'statistics' => $this->verifiedStatistics()
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

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Sí (Over 2.5)', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'No (Under 2.5)', 'is_correct' => false]);

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

    public function test_evaluate_possession_with_flat_statistics(): void
    {
        $this->match->update([
            'statistics' => json_encode([
                'source' => 'Gemini (web search - VERIFIED)',
                'verified' => true,
                'home_possession' => 72,
                'away_possession' => 28
            ])
        ]);
        $this->match->refresh();

        $question = Question::create([
            'title' => '¿Quién tendrá más posesión de balón?',
            'type' => 'posesion',
            'match_id' => $this->match->id,
            'group_id' => $this->group->id,
            'points' => 200,
            'available_until' => now()->addHours(24)
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Arsenal', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'Liverpool', 'is_correct' => false]);

        $question->refresh();
        $correctOptions = $this->service->evaluateQuestion($question, $this->match);

        $this->assertCount(1, $correctOptions);
        $this->assertSame([$question->options[0]->id], $correctOptions);
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
            ]),
            'statistics' => json_encode([
                'source' => 'Gemini (web search - VERIFIED)',
                'verified' => true
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

    public function test_unknown_question_uses_gemini_fallback(): void
    {
        config(['question_evaluation.gemini_fallback_enabled' => true]);

        $question = Question::create([
            'title' => '¿Cuál será el momento más divertido del partido?',
            'type' => 'ludica_custom',
            'match_id' => $this->match->id,
            'group_id' => $this->group->id,
            'points' => 200,
            'available_until' => now()->addHours(24)
        ]);

        $optionA = QuestionOption::create(['question_id' => $question->id, 'text' => 'Celebración de Arsenal', 'is_correct' => false]);
        $optionB = QuestionOption::create(['question_id' => $question->id, 'text' => 'Remontada de Liverpool', 'is_correct' => false]);

        $this->geminiMock
            ->shouldReceive('callGemini')
            ->once()
            ->andReturn(['selected_options' => ['option_2']]);

        $question->refresh();
        $correctOptions = $this->service->evaluateQuestion($question, $this->match);

        $this->assertSame([$optionB->id], $correctOptions);
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

    private function verifiedStatistics(array $overrides = []): string
    {
        $base = [
            'source' => 'Gemini (web search - VERIFIED)',
            'verified' => true,
            'home' => ['possession' => 65, 'passes' => 500, 'shots' => 10],
            'away' => ['possession' => 35, 'passes' => 300, 'shots' => 4]
        ];

        if (!empty($overrides)) {
            $base = array_replace_recursive($base, $overrides);
        }

        return json_encode($base);
    }
}
