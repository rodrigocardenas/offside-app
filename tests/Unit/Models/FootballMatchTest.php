<?php

namespace Tests\Unit\Models;

use App\Models\FootballMatch;
use App\Models\Team;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class FootballMatchTest extends TestCase
{
    use DatabaseMigrations;
    /**
     * Test getFeaturedPriorityScore returns correct values
     */
    public function test_get_featured_priority_score_returns_1_0_when_both_teams_featured(): void
    {
        // Create featured teams
        $homeTeam = Team::create([
            'name' => 'Real Madrid',
            'api_name' => 'real_madrid',
            'is_featured' => true,
            'type' => 'club',
        ]);

        $awayTeam = Team::create([
            'name' => 'Barcelona',
            'api_name' => 'barcelona',
            'is_featured' => true,
            'type' => 'club',
        ]);

        // Create match
        $match = FootballMatch::create([
            'home_team' => 'real_madrid',
            'away_team' => 'barcelona',
            'date' => now()->addDay(),
            'status' => 'Not Started',
        ]);

        // Assert
        $this->assertEquals(1.0, $match->getFeaturedPriorityScore());
    }

    /**
     * Test getFeaturedPriorityScore returns 0.7 when one team is featured
     */
    public function test_get_featured_priority_score_returns_0_7_when_one_team_featured(): void
    {
        // Create one featured, one regular
        $homeTeam = Team::create([
            'name' => 'Real Madrid',
            'api_name' => 'real_madrid',
            'is_featured' => true,
            'type' => 'club',
        ]);

        $awayTeam = Team::create([
            'name' => 'Almería',
            'api_name' => 'almeria',
            'is_featured' => false,
            'type' => 'club',
        ]);

        // Create match
        $match = FootballMatch::create([
            'home_team' => 'real_madrid',
            'away_team' => 'almeria',
            'date' => now()->addDay(),
            'status' => 'Not Started',
        ]);

        // Assert
        $this->assertEquals(0.7, $match->getFeaturedPriorityScore());
    }

    /**
     * Test getFeaturedPriorityScore returns 0.3 when no teams are featured
     */
    public function test_get_featured_priority_score_returns_0_3_when_no_teams_featured(): void
    {
        // Create non-featured teams
        $homeTeam = Team::create([
            'name' => 'Almería',
            'api_name' => 'almeria',
            'is_featured' => false,
            'type' => 'club',
        ]);

        $awayTeam = Team::create([
            'name' => 'Osasuna',
            'api_name' => 'osasuna',
            'is_featured' => false,
            'type' => 'club',
        ]);

        // Create match
        $match = FootballMatch::create([
            'home_team' => 'almeria',
            'away_team' => 'osasuna',
            'date' => now()->addDay(),
            'status' => 'Not Started',
        ]);

        // Assert
        $this->assertEquals(0.3, $match->getFeaturedPriorityScore());
    }

    /**
     * Test getQuestionFeaturedValue returns true when any team is featured
     */
    public function test_get_question_featured_value_returns_true_when_any_team_featured(): void
    {
        // Create featured team
        $homeTeam = Team::create([
            'name' => 'Real Madrid',
            'api_name' => 'real_madrid',
            'is_featured' => true,
            'type' => 'club',
        ]);

        $awayTeam = Team::create([
            'name' => 'Almería',
            'api_name' => 'almeria',
            'is_featured' => false,
            'type' => 'club',
        ]);

        // Create match
        $match = FootballMatch::create([
            'home_team' => 'real_madrid',
            'away_team' => 'almeria',
            'date' => now()->addDay(),
            'status' => 'Not Started',
        ]);

        // Assert
        $this->assertTrue($match->getQuestionFeaturedValue());
    }

    /**
     * Test getQuestionFeaturedValue returns false when no teams are featured
     */
    public function test_get_question_featured_value_returns_false_when_no_teams_featured(): void
    {
        // Create non-featured teams
        $homeTeam = Team::create([
            'name' => 'Almería',
            'api_name' => 'almeria',
            'is_featured' => false,
            'type' => 'club',
        ]);

        $awayTeam = Team::create([
            'name' => 'Osasuna',
            'api_name' => 'osasuna',
            'is_featured' => false,
            'type' => 'club',
        ]);

        // Create match
        $match = FootballMatch::create([
            'home_team' => 'almeria',
            'away_team' => 'osasuna',
            'date' => now()->addDay(),
            'status' => 'Not Started',
        ]);

        // Assert
        $this->assertFalse($match->getQuestionFeaturedValue());
    }

    /**
     * Test orderByFeaturedTeams scope orders matches correctly
     */
    public function test_order_by_featured_teams_scope_orders_correctly(): void
    {
        // Create teams
        $featuredTeam1 = Team::create([
            'name' => 'Real Madrid',
            'api_name' => 'real_madrid',
            'is_featured' => true,
            'type' => 'club',
        ]);

        $featuredTeam2 = Team::create([
            'name' => 'Barcelona',
            'api_name' => 'barcelona',
            'is_featured' => true,
            'type' => 'club',
        ]);

        $regularTeam1 = Team::create([
            'name' => 'Almería',
            'api_name' => 'almeria',
            'is_featured' => false,
            'type' => 'club',
        ]);

        $regularTeam2 = Team::create([
            'name' => 'Osasuna',
            'api_name' => 'osasuna',
            'is_featured' => false,
            'type' => 'club',
        ]);

        // Create matches in random order
        $regularMatch = FootballMatch::create([
            'home_team' => 'almeria',
            'away_team' => 'osasuna',
            'date' => now()->addHours(1),
            'status' => 'Not Started',
        ]);

        $derbyMatch = FootballMatch::create([
            'home_team' => 'real_madrid',
            'away_team' => 'almeria',
            'date' => now()->addHours(2),
            'status' => 'Not Started',
        ]);

        $classicMatch = FootballMatch::create([
            'home_team' => 'real_madrid',
            'away_team' => 'barcelona',
            'date' => now()->addHours(3),
            'status' => 'Not Started',
        ]);

        // Get ordered matches
        $ordered = FootballMatch::orderByFeaturedTeams()->get();

        // Assert order: Classic (1.0) -> Derby (0.7) -> Regular (0.3)
        $this->assertEquals($classicMatch->id, $ordered->first()->id);
        $this->assertEquals($regularMatch->id, $ordered->last()->id);
    }
}
