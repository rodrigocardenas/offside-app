<?php

namespace Tests\Feature\Ranking;

use Tests\TestCase;
use App\Models\User;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Answer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can view global rankings
     */
    public function test_user_can_view_global_rankings(): void
    {
        $user = User::factory()->create();
        User::factory()->count(5)->create();

        $response = $this->actingAs($user)->getJson('/api/rankings/global');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => ['position', 'user_id', 'name', 'total_points'],
                 ]);
    }

    /**
     * Test user appears in global rankings
     */
    public function test_user_appears_in_global_rankings(): void
    {
        $user = User::factory()->create();
        $users = User::factory()->count(2)->create();

        // Create some answered questions
        $question = Question::factory()->create(['status' => 'closed']);
        $correctOption = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        // User answers correctly
        Answer::factory()->create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'option_id' => $correctOption->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/rankings/global');

        $response->assertStatus(200);
        $rankings = $response->json();

        $userRanking = collect($rankings)->firstWhere('user_id', $user->id);
        $this->assertNotNull($userRanking);
        $this->assertGreaterThan(0, $userRanking['total_points']);
    }

    /**
     * Test user can view ranking stats
     */
    public function test_user_can_view_their_stats(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/rankings/stats');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'total_predictions',
                     'correct_predictions',
                     'incorrect_predictions',
                     'accuracy',
                     'total_points',
                 ]);
    }

    /**
     * Test rankings are ordered by points
     */
    public function test_rankings_ordered_by_total_points(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $response = $this->actingAs($user1)->getJson('/api/rankings/global');

        $response->assertStatus(200);
        $rankings = $response->json();

        for ($i = 1; $i < count($rankings); $i++) {
            $this->assertGreaterThanOrEqual(
                $rankings[$i]['total_points'],
                $rankings[$i - 1]['total_points']
            );
        }
    }
}
