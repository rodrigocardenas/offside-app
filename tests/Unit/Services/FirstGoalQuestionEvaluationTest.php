<?php

namespace Tests\Unit\Services;

use App\Models\FootballMatch;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Services\QuestionEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirstGoalQuestionEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private QuestionEvaluationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuestionEvaluationService();
    }

    /**
     * Test que verifica que la evaluación de "Primer Gol" funciona
     * con el nuevo formato de eventos de API Football.
     *
     * Formato nuevo (API Football):
     * {"time": 16, "type": "Goal", "team": "Inter", "player": "L. Martinez", ...}
     *
     * Formato antiguo (Gemini):
     * {"minute": "15", "type": "GOAL", "team": "HOME", ...}
     */
    public function test_evaluates_first_goal_with_api_football_format()
    {
        // Create a match with API Football format events
        $match = FootballMatch::create([
            'external_id' => 9999,
            'home_team' => 'Cremonese',
            'away_team' => 'Inter',
            'date' => now()->subHours(2),
            'status' => 'Finished',
            'home_team_score' => 0,
            'away_team_score' => 2,
            'league' => 'Serie A',
            // New format from API Football with "time" and "Goal" (not "GOAL")
            'events' => json_encode([
                ['time' => 16, 'type' => 'Goal', 'team' => 'Inter', 'player' => 'L. Martinez', 'assist' => 'F. Dimarco', 'detail' => 'Normal Goal'],
                ['time' => 31, 'type' => 'Goal', 'team' => 'Inter', 'player' => 'P. Zielinski', 'assist' => 'Luis Henrique', 'detail' => 'Normal Goal'],
                ['time' => 45, 'type' => 'Card', 'team' => 'Cremonese', 'player' => 'F. Ceccherini', 'detail' => 'Yellow Card'],
            ]),
            'statistics' => json_encode([
                'home' => [],
                'away' => []
            ])
        ]);

        // Create a "First Goal" question
        $question = Question::create([
            'title' => '¿Cuál equipo anotará el primer gol?',
            'type' => 'predictive',
            'match_id' => $match->id,
            'group_id' => 1,
            'points' => 100,
            'available_until' => now()->addHours(24)
        ]);

        // Add options
        $options = [
            QuestionOption::create(['question_id' => $question->id, 'text' => 'Cremonese', 'is_correct' => false]),
            QuestionOption::create(['question_id' => $question->id, 'text' => 'Inter', 'is_correct' => false]),
            QuestionOption::create(['question_id' => $question->id, 'text' => 'Ninguno', 'is_correct' => false]),
        ];

        $question->refresh();

        // Evaluate the question
        $correctOptionIds = $this->service->evaluateQuestion($question, $match);

        // Assert: The correct answer should be "Inter"
        $this->assertCount(1, $correctOptionIds);
        $this->assertEquals($options[1]->id, $correctOptionIds[0]);
        $this->assertEquals('Inter', $options[1]->text);
    }

    /**
     * Test last goal evaluation with new format
     */
    public function test_evaluates_last_goal_with_api_football_format()
    {
        $match = FootballMatch::create([
            'external_id' => 10000,
            'home_team' => 'Cremonese',
            'away_team' => 'Inter',
            'date' => now()->subHours(2),
            'status' => 'Finished',
            'home_team_score' => 0,
            'away_team_score' => 2,
            'league' => 'Serie A',
            'events' => json_encode([
                ['time' => 16, 'type' => 'Goal', 'team' => 'Inter', 'player' => 'L. Martinez'],
                ['time' => 31, 'type' => 'Goal', 'team' => 'Inter', 'player' => 'P. Zielinski'],
            ]),
            'statistics' => json_encode(['home' => [], 'away' => []])
        ]);

        $question = Question::create([
            'title' => '¿Cuál equipo anotará el último gol?',
            'type' => 'predictive',
            'match_id' => $match->id,
            'group_id' => 1,
            'points' => 100,
            'available_until' => now()->addHours(24)
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Cremonese', 'is_correct' => false]);
        $correctOption = QuestionOption::create(['question_id' => $question->id, 'text' => 'Inter', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'Ninguno', 'is_correct' => false]);

        $question->refresh();
        $correctOptionIds = $this->service->evaluateQuestion($question, $match);

        $this->assertCount(1, $correctOptionIds);
        $this->assertEquals($correctOption->id, $correctOptionIds[0]);
    }

    /**
     * Test goal before minute X with new format
     */
    public function test_evaluates_goal_before_minute_with_api_football_format()
    {
        $match = FootballMatch::create([
            'external_id' => 10001,
            'home_team' => 'Cremonese',
            'away_team' => 'Inter',
            'date' => now()->subHours(2),
            'status' => 'Finished',
            'home_team_score' => 0,
            'away_team_score' => 2,
            'league' => 'Serie A',
            'events' => json_encode([
                ['time' => 16, 'type' => 'Goal', 'team' => 'Inter', 'player' => 'L. Martinez'],
                ['time' => 45, 'type' => 'Goal', 'team' => 'Inter', 'player' => 'P. Zielinski'],
            ]),
            'statistics' => json_encode(['home' => [], 'away' => []])
        ]);

        // Question: Will there be a goal before minute 20?
        $question = Question::create([
            'title' => '¿Habrá gol antes de los 20 minutos?',
            'type' => 'predictive',
            'match_id' => $match->id,
            'group_id' => 1,
            'points' => 100,
            'available_until' => now()->addHours(24),
            'threshold_minutes' => 20
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Sí', 'is_correct' => false]);
        $correctOption = QuestionOption::create(['question_id' => $question->id, 'text' => 'No', 'is_correct' => false]);

        $question->refresh();
        $correctOptionIds = $this->service->evaluateQuestion($question, $match);

        // Should find that Inter scored at minute 16 (before 20), so "Sí" is correct
        $this->assertCount(1, $correctOptionIds);
    }

    /**
     * Test que valida que los penales fallados NO se cuentan como goles
     * Regresión: Match 755 - Wolves vs Leicester (0-0 pero con un "Missed Penalty")
     * 
     * El evento es: {"time":43,"type":"Goal","team":"Wolves","detail":"Missed Penalty"}
     * Este evento NO debe contar como gol para la pregunta "¿Quién anotará el primer gol?"
     */
    public function test_missed_penalty_is_not_counted_as_goal()
    {
        // Create match similar to match 755 (Wolves vs Leicester)
        $match = FootballMatch::create([
            'external_id' => 755,
            'home_team' => 'Wolves',
            'away_team' => 'Leicester',
            'date' => now()->subHours(2),
            'status' => 'Finished',
            'home_team_score' => 0,
            'away_team_score' => 0,
            'league' => 'Premier League',
            // Events with a missed penalty that has type "Goal" but detail "Missed Penalty"
            'events' => json_encode([
                ['time' => 43, 'type' => 'Goal', 'team' => 'Wolves', 'player' => 'T. Arokodare', 'assist' => 'T. Arokodare', 'detail' => 'Missed Penalty'],
                ['time' => 55, 'type' => 'Card', 'team' => 'Leicester', 'player' => 'W. Ndidi', 'detail' => 'Yellow Card'],
                ['time' => 89, 'type' => 'Card', 'team' => 'Wolves', 'player' => 'R. Aït-Nouri', 'detail' => 'Yellow Card'],
            ]),
            'statistics' => json_encode(['home' => [], 'away' => []])
        ]);

        // Create first goal question
        $question = Question::create([
            'title' => '¿Cuál equipo anotará el primer gol?',
            'type' => 'predictive',
            'match_id' => $match->id,
            'group_id' => 1,
            'points' => 100,
            'available_until' => now()->addHours(24)
        ]);

        // Add options
        $wolvesOption = QuestionOption::create(['question_id' => $question->id, 'text' => 'Wolves']);
        $leicesterOption = QuestionOption::create(['question_id' => $question->id, 'text' => 'Leicester']);
        $noneOption = QuestionOption::create(['question_id' => $question->id, 'text' => 'Ninguno']);

        $question->refresh();
        $correctOptionIds = $this->service->evaluateQuestion($question, $match);

        // Assert: The correct answer should be "Ninguno" (no one scored, the penalty was missed)
        // NOT "Wolves" (which was incorrectly selected before the fix)
        $this->assertCount(1, $correctOptionIds);
        $this->assertEquals($noneOption->id, $correctOptionIds[0], 
            'The missed penalty at 43\' should NOT count as a goal. Correct option should be "Ninguno".');
    }

    /**
     * Test que valida que los goles válidos sí se cuentan como goles
     * Incluso si hay un penales fallado antes
     */
    public function test_valid_goal_counts_when_missed_penalty_exists()
    {
        $match = FootballMatch::create([
            'external_id' => 756,
            'home_team' => 'Wolves',
            'away_team' => 'Leicester',
            'date' => now()->subHours(2),
            'status' => 'Finished',
            'home_team_score' => 1,
            'away_team_score' => 0,
            'league' => 'Premier League',
            // Events with missed penalty followed by valid goal
            'events' => json_encode([
                ['time' => 43, 'type' => 'Goal', 'team' => 'Wolves', 'detail' => 'Missed Penalty'],
                ['time' => 67, 'type' => 'Goal', 'team' => 'Wolves', 'detail' => 'Normal Goal'],
            ]),
            'statistics' => json_encode(['home' => [], 'away' => []])
        ]);

        $question = Question::create([
            'title' => '¿Cuál equipo anotará el primer gol?',
            'type' => 'predictive',
            'match_id' => $match->id,
            'group_id' => 1,
            'points' => 100,
            'available_until' => now()->addHours(24)
        ]);

        $wolvesOption = QuestionOption::create(['question_id' => $question->id, 'text' => 'Wolves']);
        $leicesterOption = QuestionOption::create(['question_id' => $question->id, 'text' => 'Leicester']);
        $noneOption = QuestionOption::create(['question_id' => $question->id, 'text' => 'Ninguno']);

        $question->refresh();
        $correctOptionIds = $this->service->evaluateQuestion($question, $match);

        // Assert: The valid goal at 67' should be counted, so Wolves is the correct answer
        $this->assertCount(1, $correctOptionIds);
        $this->assertEquals($wolvesOption->id, $correctOptionIds[0],
            'The valid goal at 67\' should be counted even if there was a missed penalty before.');
    }
}
