<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Answer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QuestionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test question has many options
     */
    public function test_question_has_many_options(): void
    {
        $question = Question::factory()->create();
        QuestionOption::factory()->count(3)->create(['question_id' => $question->id]);

        $this->assertCount(3, $question->options);
    }

    /**
     * Test question can be marked as correct
     */
    public function test_question_can_mark_correct_answer(): void
    {
        $question = Question::factory()->create();
        $correctOption = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'is_correct' => true,
        ]);

        $this->assertTrue($correctOption->is_correct);
    }

    /**
     * Test question has many answers
     */
    public function test_question_has_many_answers(): void
    {
        $question = Question::factory()->create();
        Answer::factory()->count(4)->create(['question_id' => $question->id]);

        $this->assertCount(4, $question->answers);
    }
}
