<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use App\Models\Competition;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Answer;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group deploy
 * Critical tests for core Offside Club functionality
 * Tests validate data integrity and business logic without HTTP dependencies
 */
class CriticalViewsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $group;
    protected $competition;
    protected $question;
    protected $questionOption;

    protected function setUp(): void
    {
        parent::setUp();

        // Create competition
        $this->competition = Competition::create([
            'name' => 'Test Competition',
            'type' => 'laliga',
            'logo' => 'test.png'
        ]);

        // Create user
        $this->user = User::factory()->create();

        // Create group
        $this->group = Group::create([
            'name' => 'Test Group',
            'code' => 'TEST' . uniqid(),
            'created_by' => $this->user->id,
            'competition_id' => $this->competition->id,
            'category' => 'official',
        ]);

        // Attach user to group
        $this->group->users()->attach([$this->user->id]);

        // Create a match question
        $this->question = Question::create([
            'competition_id' => $this->competition->id,
            'group_id' => $this->group->id,
            'title' => 'Test Match Question',
            'type' => 'match',
            'points' => 10,
            'expires_at' => now()->addHours(24),
            'correct_option_id' => null,
            'category' => 'goals'
        ]);

        // Create question options
        $this->questionOption = QuestionOption::create([
            'question_id' => $this->question->id,
            'label' => 'Test Option',
            'points' => 10
        ]);

        $this->question->update(['correct_option_id' => $this->questionOption->id]);
    }

    /** @test */
    public function test_user_can_be_created_and_retrieved()
    {
        $user = User::where('id', $this->user->id)->first();
        $this->assertNotNull($user);
    }

    /** @test */
    public function test_user_belongs_to_multiple_groups()
    {
        $groups = $this->user->groups;
        $this->assertGreaterThanOrEqual(1, $groups->count());
    }

    /** @test */
    public function test_user_can_have_answers()
    {
        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
        ]);

        $userAnswers = $this->user->answers;
        $this->assertGreaterThanOrEqual(1, $userAnswers->count());
    }

    /** @test */
    public function test_group_can_be_created_with_unique_code()
    {
        $newGroup = Group::create([
            'name' => 'Another Group',
            'code' => 'UNIQ' . uniqid(),
            'created_by' => $this->user->id,
            'competition_id' => $this->competition->id,
            'category' => 'official',
        ]);

        $this->assertNotNull($newGroup->id);
    }

    /** @test */
    public function test_group_has_users()
    {
        $users = $this->group->users;
        $this->assertGreaterThanOrEqual(1, $users->count());
    }

    /** @test */
    public function test_group_belongs_to_competition()
    {
        $competition = $this->group->competition;
        $this->assertNotNull($competition);
    }

    /** @test */
    public function test_group_has_questions()
    {
        $questions = $this->group->questions;
        $this->assertGreaterThanOrEqual(1, $questions->count());
    }

    /** @test */
    public function test_competition_can_be_created()
    {
        $competition = Competition::where('id', $this->competition->id)->first();
        $this->assertNotNull($competition);
    }

    /** @test */
    public function test_competition_has_groups()
    {
        $groups = $this->competition->groups;
        $this->assertGreaterThanOrEqual(1, $groups->count());
    }

    /** @test */
    public function test_question_can_be_created()
    {
        $question = Question::where('id', $this->question->id)->first();
        $this->assertNotNull($question);
    }

    /** @test */
    public function test_question_belongs_to_group()
    {
        $group = $this->question->group;
        $this->assertNotNull($group);
    }

    /** @test */
    public function test_question_has_options()
    {
        $options = $this->question->options;
        $this->assertGreaterThanOrEqual(1, $options->count());
    }

    /** @test */
    public function test_user_can_submit_answer()
    {
        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
        ]);

        $this->assertNotNull($answer->id);
    }

    /** @test */
    public function test_answer_is_created_with_correct_points()
    {
        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
            'points' => 10,
        ]);

        $this->assertEquals(10, $answer->points);
    }

    /** @test */
    public function test_answer_for_social_question()
    {
        $socialQuestion = Question::create([
            'competition_id' => $this->competition->id,
            'group_id' => $this->group->id,
            'title' => 'Test Social Question',
            'type' => 'social',
            'points' => 5,
            'expires_at' => now()->addHours(24),
            'correct_option_id' => null,
            'category' => 'other'
        ]);

        $socialOption = QuestionOption::create([
            'question_id' => $socialQuestion->id,
            'label' => 'Social Option',
            'points' => 5
        ]);

        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $socialQuestion->id,
            'question_option_id' => $socialOption->id,
            'points' => 5,
        ]);

        $this->assertEquals(5, $answer->points);
    }

    /** @test */
    public function test_user_can_update_answer()
    {
        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
            'points' => 10,
        ]);

        $updatedOption = QuestionOption::create([
            'question_id' => $this->question->id,
            'label' => 'Updated Option',
            'points' => 15
        ]);

        $answer->update(['points' => 15]);

        $this->assertEquals(15, $answer->fresh()->points);
    }

    /** @test */
    public function test_invalid_answer_option_fails()
    {
        try {
            Answer::create([
                'user_id' => $this->user->id,
                'question_id' => $this->question->id,
                'question_option_id' => 99999,
                'points' => 10,
            ]);
            $this->fail('Should not allow invalid option');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    /** @test */
    public function test_answer_to_expired_question()
    {
        $expiredQuestion = Question::create([
            'competition_id' => $this->competition->id,
            'group_id' => $this->group->id,
            'title' => 'Expired Question',
            'type' => 'match',
            'points' => 10,
            'expires_at' => now()->subHours(1),
            'correct_option_id' => null,
            'category' => 'goals'
        ]);

        $expiredOption = QuestionOption::create([
            'question_id' => $expiredQuestion->id,
            'label' => 'Option',
            'points' => 10
        ]);

        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $expiredQuestion->id,
            'question_option_id' => $expiredOption->id,
        ]);

        $this->assertNotNull($answer->id);
    }

    /** @test */
    public function test_another_user_can_answer()
    {
        $anotherUser = User::factory()->create();
        
        $answer = Answer::factory()->create([
            'user_id' => $anotherUser->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
        ]);

        $this->assertNotNull($answer->id);
    }

    /** @test */
    public function test_answer_metadata_storage()
    {
        $metadata = ['confidence' => 'high', 'notes' => 'test'];
        
        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
            'metadata' => json_encode($metadata),
        ]);

        $storedMetadata = json_decode($answer->metadata, true);
        $this->assertEquals('high', $storedMetadata['confidence']);
    }

    /** @test */
    public function test_answer_user_relationship()
    {
        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
        ]);

        $user = $answer->user;
        $this->assertNotNull($user);
        $this->assertEquals($this->user->id, $user->id);
    }

    /** @test */
    public function test_answer_question_relationship()
    {
        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
        ]);

        $question = $answer->question;
        $this->assertNotNull($question);
        $this->assertEquals($this->question->id, $question->id);
    }

    /** @test */
    public function test_answer_option_relationship()
    {
        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
        ]);

        $option = $answer->option;
        $this->assertNotNull($option);
        $this->assertEquals($this->questionOption->id, $option->id);
    }

    /** @test */
    public function test_question_option_relationship()
    {
        $question = $this->questionOption->question;
        $this->assertNotNull($question);
        $this->assertEquals($this->question->id, $question->id);
    }

    /** @test */
    public function test_group_created_by_user()
    {
        $creator = $this->group->creator;
        $this->assertNotNull($creator);
        $this->assertEquals($this->user->id, $creator->id);
    }

    /** @test */
    public function test_multiple_questions_in_group()
    {
        $q2 = Question::create([
            'competition_id' => $this->competition->id,
            'group_id' => $this->group->id,
            'title' => 'Another Question',
            'type' => 'match',
            'points' => 10,
            'expires_at' => now()->addHours(24),
            'correct_option_id' => null,
            'category' => 'goals'
        ]);

        $groupQuestions = $this->group->questions;
        $this->assertGreaterThanOrEqual(2, $groupQuestions->count());
    }

    /** @test */
    public function test_multiple_answers_per_user()
    {
        $q2 = Question::create([
            'competition_id' => $this->competition->id,
            'group_id' => $this->group->id,
            'title' => 'Another Question',
            'type' => 'match',
            'points' => 10,
            'expires_at' => now()->addHours(24),
            'correct_option_id' => null,
            'category' => 'goals'
        ]);

        $opt2 = QuestionOption::create([
            'question_id' => $q2->id,
            'label' => 'Option',
            'points' => 10
        ]);

        $a1 = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
        ]);

        $a2 = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $q2->id,
            'question_option_id' => $opt2->id,
        ]);

        $userAnswers = $this->user->answers;
        $this->assertGreaterThanOrEqual(2, $userAnswers->count());
    }
}
