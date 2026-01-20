<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\FootballMatch;
use App\Models\Team;
use App\Models\Competition;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use App\Support\TeamResolver;

class UpdateFootballDataCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        TeamResolver::resetCache();
    }

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

    public function test_new_matches_store_not_started_status(): void
    {
        Http::fake([
            'https://api.football-data.org/v4/competitions/PL/matches*' => Http::response([
                'matches' => [
                    [
                        'id' => 4001,
                        'homeTeam' => ['id' => 1, 'name' => 'Arsenal FC'],
                        'awayTeam' => ['id' => 2, 'name' => 'Liverpool FC'],
                        'utcDate' => now()->addDay()->toIso8601String(),
                        'status' => 'TIMED',
                        'score' => ['fullTime' => ['home' => null, 'away' => null]],
                        'matchday' => 21
                    ]
                ]
            ], 200)
        ]);

        $this->artisan('app:update-football-data', [
            'league' => 'premier-league',
            '--days-ahead' => 7
        ])->assertExitCode(0);

        $match = FootballMatch::where('external_id', 4001)->first();

        $this->assertNotNull($match);
        $this->assertSame('Not Started', $match->status);
    }

    public function test_command_uses_team_mapping_for_canonical_names(): void
    {
        $home = Team::create([
            'name' => 'Arsenal',
            'api_name' => 'Arsenal FC',
            'short_name' => 'ARS',
            'external_id' => 1,
        ]);

        $away = Team::create([
            'name' => 'Liverpool',
            'api_name' => 'Liverpool FC',
            'short_name' => 'LIV',
            'external_id' => 2,
        ]);

        Http::fake([
            'https://api.football-data.org/v4/competitions/PL/matches*' => Http::response([
                'matches' => [
                    [
                        'id' => 2001,
                        'homeTeam' => ['id' => 1, 'name' => 'Arsenal FC'],
                        'awayTeam' => ['id' => 2, 'name' => 'Liverpool FC'],
                        'utcDate' => '2026-02-01T18:00:00Z',
                        'status' => 'SCHEDULED',
                        'score' => ['fullTime' => ['home' => null, 'away' => null]],
                        'matchday' => 22
                    ]
                ]
            ], 200)
        ]);

        $this->artisan('app:update-football-data', [
            'league' => 'premier-league',
            '--days-ahead' => 7
        ])->assertExitCode(0);

        $this->assertDatabaseHas('football_matches', [
            'external_id' => 2001,
            'home_team' => 'Arsenal',
            'away_team' => 'Liverpool',
        ]);
    }

    public function test_command_updates_api_name_using_fuzzy_match(): void
    {
        $home = Team::create([
            'name' => 'Club Atlético River Plate',
            'api_name' => null,
            'short_name' => 'CAR',
            'external_id' => 'river-plate-local',
            'type' => 'club',
        ]);

        $away = Team::create([
            'name' => 'Boca Juniors',
            'api_name' => 'Boca Juniors',
            'short_name' => 'BOC',
            'external_id' => 'boca-juniors',
            'type' => 'club',
        ]);

        Http::fake([
            'https://api.football-data.org/v4/competitions/PD/matches*' => Http::response([
                'matches' => [
                    [
                        'id' => 5001,
                        'homeTeam' => ['id' => null, 'name' => 'River Plate FC'],
                        'awayTeam' => ['id' => null, 'name' => 'Boca Juniors'],
                        'utcDate' => now()->addDays(3)->toIso8601String(),
                        'status' => 'TIMED',
                        'score' => ['fullTime' => ['home' => null, 'away' => null]],
                        'matchday' => 23
                    ]
                ]
            ], 200)
        ]);

        $this->artisan('app:update-football-data', [
            'league' => 'la-liga',
            '--days-ahead' => 7
        ])->assertExitCode(0);

        $home->refresh();

        $this->assertSame('River Plate FC', $home->api_name);

        $this->assertDatabaseHas('football_matches', [
            'external_id' => 5001,
            'home_team' => 'Club Atlético River Plate',
            'away_team' => 'Boca Juniors',
        ]);
    }

    public function test_command_saves_matches_in_utc(): void
    {
        Http::fake([
            'https://api.football-data.org/v4/competitions/PL/matches*' => Http::response([
                'matches' => [
                    [
                        'id' => 3001,
                        'homeTeam' => ['id' => 10, 'name' => 'Chelsea FC'],
                        'awayTeam' => ['id' => 20, 'name' => 'Tottenham Hotspur'],
                        'utcDate' => '2026-03-10T21:00:00+02:00',
                        'status' => 'SCHEDULED',
                        'score' => ['fullTime' => ['home' => null, 'away' => null]],
                        'matchday' => 25
                    ]
                ]
            ], 200)
        ]);

        $this->artisan('app:update-football-data', [
            'league' => 'premier-league',
            '--days-ahead' => 30
        ])->assertExitCode(0);

        $match = FootballMatch::first();
        $expectedUtcDate = Carbon::parse('2026-03-10T21:00:00+02:00')->utc();

        $this->assertEquals(
            $expectedUtcDate->format('Y-m-d H:i:s'),
            $match->getRawOriginal('date')
        );
    }
}
