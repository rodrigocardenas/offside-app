<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\FootballMatch;
use App\Models\Team;
use App\Models\Competition;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateFootballDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_downloads_fixtures_from_api(): void
    {
        Http::fake([
            'https://api.football-data.org/v4/competitions/PL/matches*' => Http::response([
                'matches' => [
                    [
                        'id' => 1001,
                        'homeTeam' => ['id' => 1, 'name' => 'Arsenal FC'],
                        'awayTeam' => ['id' => 2, 'name' => 'Liverpool FC'],
                        'utcDate' => '2026-01-15T20:00:00Z',
                        'status' => 'SCHEDULED',
                        'score' => ['fullTime' => ['home' => null, 'away' => null]],
                        'matchday' => 20
                    ]
                ]
            ], 200)
        ]);

        $this->artisan('app:update-football-data', [
            'league' => 'premier-league',
            '--days-ahead' => 7
        ])->assertExitCode(0);

        $this->assertDatabaseHas('football_matches', [
            'external_id' => 1001,
            'home_team' => 'Arsenal FC',
            'away_team' => 'Liverpool FC'
        ]);

        $this->assertEquals(1, FootballMatch::count());
    }

    public function test_command_updates_existing_matches(): void
    {
        FootballMatch::create([
            'external_id' => 1001,
            'home_team' => 'Arsenal FC',
            'away_team' => 'Liverpool FC',
            'date' => now()->addDays(5),
            'status' => 'SCHEDULED',
            'league' => 'PL'
        ]);

        Http::fake([
            'https://api.football-data.org/v4/competitions/PL/matches*' => Http::response([
                'matches' => [
                    [
                        'id' => 1001,
                        'homeTeam' => ['id' => 1, 'name' => 'Arsenal FC'],
                        'awayTeam' => ['id' => 2, 'name' => 'Liverpool FC'],
                        'utcDate' => '2026-01-15T20:00:00Z',
                        'status' => 'LIVE',
                        'score' => ['fullTime' => ['home' => 1, 'away' => 1]],
                        'matchday' => 20
                    ]
                ]
            ], 200)
        ]);

        $this->artisan('app:update-football-data', [
            'league' => 'premier-league',
            '--days-ahead' => 7
        ])->assertExitCode(0);

        $this->assertDatabaseHas('football_matches', [
            'external_id' => 1001,
            'status' => 'LIVE',
            'home_team_score' => 1,
            'away_team_score' => 1
        ]);

        $this->assertEquals(1, FootballMatch::count());
    }

    public function test_command_handles_api_errors(): void
    {
        Http::fake([
            'https://api.football-data.org/v4/competitions/PL/matches*' => Http::response(
                ['error' => 'Unauthorized'],
                401
            )
        ]);

        $this->artisan('app:update-football-data', [
            'league' => 'premier-league',
            '--days-ahead' => 7
        ])->assertExitCode(1);

        $this->assertEquals(0, FootballMatch::count());
    }

    public function test_command_supports_all_leagues(): void
    {
        $leagues = [
            'la-liga' => 'PD',
            'premier-league' => 'PL',
            'champions-league' => 'CL',
            'serie-a' => 'SA'
        ];

        foreach ($leagues as $leagueArg => $leagueCode) {
            Http::fake([
                'https://api.football-data.org/v4/competitions/' . $leagueCode . '/matches*' => Http::response([
                    'matches' => [
                        [
                            'id' => random_int(1000, 9999),
                            'homeTeam' => ['id' => 1, 'name' => 'Team A'],
                            'awayTeam' => ['id' => 2, 'name' => 'Team B'],
                            'utcDate' => now()->addDays(1)->toIso8601String(),
                            'status' => 'SCHEDULED',
                            'score' => ['fullTime' => ['home' => null, 'away' => null]],
                            'matchday' => 1
                        ]
                    ]
                ], 200)
            ]);

            $this->artisan('app:update-football-data', [
                'league' => $leagueArg,
                '--days-ahead' => 7
            ])->assertExitCode(0);
        }

        $this->assertEquals(4, FootballMatch::count());
    }
}
