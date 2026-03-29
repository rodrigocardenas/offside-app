<?php

namespace Tests\Feature\Predictions;

use Tests\TestCase;
use App\Models\User;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Answer;
use App\Models\FootballMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PredictionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can submit a prediction
     */
    public function test_user_can_submit_a_prediction(): void
    {
        $user = User::factory()->create();
        $question = Question::factory()->create([
            'status' => 'active',
        ]);
        $option = QuestionOption::factory()->create(['question_id' => $question->id]);

        $response = $this->actingAs($user)->postJson('/api/predictions/submit', [
            'question_id' => $question->id,
            'option_id' => $option->id,
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'user_id', 'question_id', 'option_id']);

        $this->assertDatabaseHas('answers', [
            'user_id' => $user->id,
            'question_id' => $question->id,
            'option_id' => $option->id,
        ]);
    }

    /**
     * Test user cannot submit prediction for inactive question
     */
    public function test_user_cannot_predict_on_inactive_question(): void
    {
        $user = User::factory()->create();
        $question = Question::factory()->create([
            'status' => 'closed',
        ]);
        $option = QuestionOption::factory()->create(['question_id' => $question->id]);

        $response = $this->actingAs($user)->postJson('/api/predictions/submit', [
            'question_id' => $question->id,
            'option_id' => $option->id,
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test user can update their prediction
     */
    public function test_user_can_update_their_prediction(): void
    {
        $user = User::factory()->create();
        $question = Question::factory()->create(['status' => 'active']);
        $option1 = QuestionOption::factory()->create(['question_id' => $question->id]);
        $option2 = QuestionOption::factory()->create(['question_id' => $question->id]);

        $answer = Answer::factory()->create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'option_id' => $option1->id,
        ]);

        $response = $this->actingAs($user)->putJson("/api/predictions/{$answer->id}", [
            'option_id' => $option2->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('answers', [
            'id' => $answer->id,
            'option_id' => $option2->id,
        ]);
    }

    /**
     * Test user cannot predict with invalid option
     */
    public function test_user_cannot_predict_with_invalid_option(): void
    {
        $user = User::factory()->create();
        $question = Question::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->postJson('/api/predictions/submit', [
            'question_id' => $question->id,
            'option_id' => 99999, // Non-existent option
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test user cannot submit two predictions for same question
     */
    public function test_user_cannot_submit_duplicate_prediction(): void
    {
        $user = User::factory()->create();
        $question = Question::factory()->create(['status' => 'active']);
        $option = QuestionOption::factory()->create(['question_id' => $question->id]);

        // First prediction
        $this->actingAs($user)->postJson('/api/predictions/submit', [
            'question_id' => $question->id,
            'option_id' => $option->id,
        ])->assertStatus(201);

        // Try second prediction
        $response = $this->actingAs($user)->postJson('/api/predictions/submit', [
            'question_id' => $question->id,
            'option_id' => $option->id,
        ]);

        $response->assertStatus(409); // Conflict
    }
}
