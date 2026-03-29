<?php

namespace Tests\Feature\Matches;

use Tests\TestCase;
use App\Models\User;
use App\Models\FootballMatch;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MatchesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can view upcoming matches
     */
    public function test_user_can_view_upcoming_matches(): void
    {
        $user = User::factory()->create();
        FootballMatch::factory()->count(3)->create([
            'match_date' => now()->addDays(1),
        ]);

        $response = $this->actingAs($user)->getJson('/api/matches/upcoming');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => ['id', 'home_team', 'away_team', 'match_date'],
                 ])
                 ->assertJsonCount(3);
    }

    /**
     * Test user can view match details with questions
     */
    public function test_user_can_view_match_details(): void
    {
        $user = User::factory()->create();
        $match = FootballMatch::factory()->create();
        Question::factory()->count(2)->create([
            'match_id' => $match->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->getJson("/api/matches/{$match->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'id',
                     'home_team',
                     'away_team',
                     'match_date',
                     'questions' => [
                         '*' => ['id', 'text', 'options'],
                     ],
                 ]);
    }

    /**
     * Test user can filter matches by date
     */
    public function test_user_can_filter_matches_by_date(): void
    {
        $user = User::factory()->create();

        FootballMatch::factory()->create([
            'match_date' => now()->addDays(1),
        ]);

        FootballMatch::factory()->create([
            'match_date' => now()->addDays(7),
        ]);

        $response = $this->actingAs($user)->getJson('/api/matches/upcoming?days=3');

        $response->assertStatus(200)
                 ->assertJsonCount(1);
    }

    /**
     * Test user can view match results
     */
    public function test_user_can_view_match_results(): void
    {
        $user = User::factory()->create();
        $match = FootballMatch::factory()->create([
            'status' => 'finished',
            'home_score' => 2,
            'away_score' => 1,
        ]);

        $response = $this->actingAs($user)->getJson("/api/matches/{$match->id}/result");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'id',
                     'home_score',
                     'away_score',
                     'status',
                 ]);
    }
}
