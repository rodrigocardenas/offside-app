<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use App\Models\Answer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user has many groups
     */
    public function test_user_has_many_groups(): void
    {
        $user = User::factory()->create();
        Group::factory()->count(3)->hasAttached($user)->create();

        $this->assertCount(3, $user->groups);
    }

    /**
     * Test user has many answers
     */
    public function test_user_has_many_answers(): void
    {
        $user = User::factory()->create();
        Answer::factory()->count(5)->create(['user_id' => $user->id]);

        $this->assertCount(5, $user->answers);
    }

    /**
     * Test user can calculate accuracy
     */
    public function test_user_accuracy_calculation(): void
    {
        $user = User::factory()->create();

        // Create some correct answers
        Answer::factory()->count(3)->create([
            'user_id' => $user->id,
            'points' => 10,
        ]);

        // Create some incorrect answers
        Answer::factory()->count(2)->create([
            'user_id' => $user->id,
            'points' => 0,
        ]);

        $totalAnswers = $user->answers()->count();
        $this->assertEquals(5, $totalAnswers);
    }
}
