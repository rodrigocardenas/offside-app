<?php

namespace Tests\Unit\Services;

use App\Models\FootballMatch;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Services\QuestionEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewQuestionTypesTest extends TestCase
{
    use RefreshDatabase;

    private QuestionEvaluationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuestionEvaluationService();
    }

    /**
     * Test S1: Late Goal (Gol en últimos 15 minutos)
     */
    public function test_evaluates_late_goal()
    {
        // Partido con gol en minuto 80 (últimos 15 minutos)
        $match = FootballMatch::create([
            'external_id' => 9001,
            'home_team' => 'Arsenal',
            'away_team' => 'Liverpool',
            'date' => now()->subHours(2),
            'status' => 'Finished',
            'home_team_score' => 0,
            'away_team_score' => 1,
            'league' => 'Premier League',
            'events' => json_encode([
                ['time' => 75, 'type' => 'Goal', 'team' => 'Liverpool', 'player' => 'Salah', 'detail' => 'Normal Goal'],
                ['time' => 80, 'type' => 'Goal', 'team' => 'Liverpool', 'player' => 'Firmino', 'detail' => 'Normal Goal'],
            ]),
            'statistics' => json_encode(['home' => [], 'away' => []])
        ]);

        $question = Question::create([
            'title' => '¿Habrá gol en los últimos 15 minutos?',
            'type' => 'predictive',
            'match_id' => $match->id,
            'group_id' => 1,
            'points' => 100,
            'available_until' => now()->addHours(24)
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Sí, habrá gol', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'No, no habrá gol', 'is_correct' => false]);

        $question->refresh();
        $correctOptionIds = $this->service->evaluateQuestion($question, $match);

        // Debería ser "Sí, habrá gol"
        $this->assertCount(1, $correctOptionIds);
        $this->assertEquals('Sí, habrá gol', $question->options->firstWhere('id', $correctOptionIds[0])->text);
    }

    /**
     * Test S5: Goal Before Halftime (Gol antes del descanso - minuto 45)
     */
    public function test_evaluates_goal_before_halftime()
    {
        // Partido con gol en minuto 30 (primer tiempo)
        $match = FootballMatch::create([
            'external_id' => 9002,
            'home_team' => 'Barcelona',
            'away_team' => 'Real Madrid',
            'date' => now()->subHours(2),
            'status' => 'Finished',
            'home_team_score' => 1,
            'away_team_score' => 2,
            'league' => 'La Liga',
            'events' => json_encode([
                ['time' => 30, 'type' => 'Goal', 'team' => 'Barcelona', 'player' => 'Messi', 'detail' => 'Normal Goal'],
                ['time' => 60, 'type' => 'Goal', 'team' => 'Real Madrid', 'player' => 'Ronaldo', 'detail' => 'Normal Goal'],
                ['time' => 75, 'type' => 'Goal', 'team' => 'Real Madrid', 'player' => 'Benzema', 'detail' => 'Normal Goal'],
            ]),
            'statistics' => json_encode(['home' => [], 'away' => []])
        ]);

        $question = Question::create([
            'title' => '¿Habrá al menos un gol en el primer tiempo?',
            'type' => 'predictive',
            'match_id' => $match->id,
            'group_id' => 1,
            'points' => 100,
            'available_until' => now()->addHours(24)
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Sí, habrá gol', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'No, no habrá gol', 'is_correct' => false]);

        $question->refresh();
        $correctOptionIds = $this->service->evaluateQuestion($question, $match);

        // Debería ser "Sí, habrá gol"
        $this->assertCount(1, $correctOptionIds);
        $this->assertEquals('Sí, habrá gol', $question->options->firstWhere('id', $correctOptionIds[0])->text);
    }

    /**
     * Test S2: Shots on Target (Tiros al arco)
     */
    public function test_evaluates_shots_on_target()
    {
        // Partido con estadísticas de tiros al arco
        $match = FootballMatch::create([
            'external_id' => 9003,
            'home_team' => 'Manchester United',
            'away_team' => 'Chelsea',
            'date' => now()->subHours(2),
            'status' => 'Finished',
            'home_team_score' => 2,
            'away_team_score' => 1,
            'league' => 'Premier League',
            'events' => json_encode([]),
            'statistics' => json_encode([
                'home' => ['shots_on_target' => 8],
                'away' => ['shots_on_target' => 4],
            ])
        ]);

        $question = Question::create([
            'title' => '¿Cuál equipo tendrá más tiros al arco?',
            'type' => 'predictive',
            'match_id' => $match->id,
            'group_id' => 1,
            'points' => 100,
            'available_until' => now()->addHours(24)
        ]);

        $homeOption = QuestionOption::create(['question_id' => $question->id, 'text' => 'Manchester United', 'is_correct' => false]);
        $awayOption = QuestionOption::create(['question_id' => $question->id, 'text' => 'Chelsea', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'Igual cantidad', 'is_correct' => false]);

        $question->refresh();
        $correctOptionIds = $this->service->evaluateQuestion($question, $match);

        // Debería ser "Manchester United" (8 > 4)
        $this->assertCount(1, $correctOptionIds);
        $this->assertEquals($homeOption->id, $correctOptionIds[0]);
    }

    /**
     * Test: No hay gol en últimos 15 minutos
     */
    public function test_evaluates_late_goal_when_no_goals()
    {
        $match = FootballMatch::create([
            'external_id' => 9004,
            'home_team' => 'Team A',
            'away_team' => 'Team B',
            'date' => now()->subHours(2),
            'status' => 'Finished',
            'home_team_score' => 1,
            'away_team_score' => 1,
            'league' => 'Test League',
            'events' => json_encode([
                ['time' => 30, 'type' => 'Goal', 'team' => 'Team A', 'player' => 'Player 1', 'detail' => 'Normal Goal'],
                ['time' => 45, 'type' => 'Goal', 'team' => 'Team B', 'player' => 'Player 2', 'detail' => 'Normal Goal'],
            ]),
            'statistics' => json_encode(['home' => [], 'away' => []])
        ]);

        $question = Question::create([
            'title' => '¿Habrá gol en los últimos 15 minutos?',
            'type' => 'predictive',
            'match_id' => $match->id,
            'group_id' => 1,
            'points' => 100,
            'available_until' => now()->addHours(24)
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Sí, habrá gol', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'No, no habrá gol', 'is_correct' => false]);

        $question->refresh();
        $correctOptionIds = $this->service->evaluateQuestion($question, $match);

        // Debería ser "No, no habrá gol"
        $this->assertCount(1, $correctOptionIds);
        $this->assertEquals('No, no habrá gol', $question->options->firstWhere('id', $correctOptionIds[0])->text);
    }

    /**
     * Test: Stats incompletas para shots on target
     */
    public function test_evaluates_shots_when_data_missing()
    {
        $match = FootballMatch::create([
            'external_id' => 9005,
            'home_team' => 'Team C',
            'away_team' => 'Team D',
            'date' => now()->subHours(2),
            'status' => 'Finished',
            'home_team_score' => 1,
            'away_team_score' => 0,
            'league' => 'Test League',
            'events' => json_encode([]),
            'statistics' => json_encode([
                'home' => [],
                'away' => [],
            ])
        ]);

        $question = Question::create([
            'title' => '¿Cuál equipo tendrá más tiros al arco?',
            'type' => 'predictive',
            'match_id' => $match->id,
            'group_id' => 1,
            'points' => 100,
            'available_until' => now()->addHours(24)
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Team C', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'Team D', 'is_correct' => false]);

        $question->refresh();
        $correctOptionIds = $this->service->evaluateQuestion($question, $match);

        // Sin datos de tiros, debería retornar array vacío
        $this->assertEmpty($correctOptionIds);
    }
}
