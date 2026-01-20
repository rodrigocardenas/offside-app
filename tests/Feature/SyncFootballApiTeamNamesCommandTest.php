<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Support\TeamResolver;

class SyncFootballApiTeamNamesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        TeamResolver::resetCache();
    }

    public function test_command_updates_existing_team_api_name(): void
    {
        $team = Team::create([
            'name' => 'Arsenal',
            'api_name' => 'Arsenal',
            'external_id' => 1,
            'type' => 'club',
        ]);

        Http::fake([
            'https://api.football-data.org/v4/competitions/PL/matches*' => Http::response([
                'matches' => [
                    [
                        'id' => 1001,
                        'homeTeam' => ['id' => 1, 'name' => 'Arsenal FC'],
                        'awayTeam' => ['id' => 2, 'name' => 'Liverpool FC'],
                        'utcDate' => now()->addDay()->toIso8601String(),
                        'status' => 'TIMED',
                        'score' => ['fullTime' => ['home' => null, 'away' => null]],
                        'matchday' => 20
                    ]
                ]
            ], 200)
        ]);

        $this->artisan('app:sync-football-api-team-names', [
            'league' => 'premier-league',
            '--days-ahead' => 7,
        ])->assertExitCode(0);

        $team->refresh();

        $this->assertSame('Arsenal FC', $team->api_name);
    }

    public function test_command_creates_missing_team_when_flag_enabled(): void
    {
        Http::fake([
            'https://api.football-data.org/v4/competitions/PL/matches*' => Http::response([
                'matches' => [
                    [
                        'id' => 2001,
                        'homeTeam' => ['id' => 10, 'name' => 'Chelsea FC'],
                        'awayTeam' => ['id' => 20, 'name' => 'Tottenham Hotspur'],
                        'utcDate' => now()->addDays(2)->toIso8601String(),
                        'status' => 'TIMED',
                        'score' => ['fullTime' => ['home' => null, 'away' => null]],
                        'matchday' => 21
                    ]
                ]
            ], 200)
        ]);

        $this->artisan('app:sync-football-api-team-names', [
            'league' => 'premier-league',
            '--create-missing' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('teams', [
            'external_id' => 20,
            'api_name' => 'Tottenham Hotspur',
            'name' => 'Tottenham Hotspur',
        ]);
    }

    public function test_command_updates_api_name_with_fuzzy_matching(): void
    {
        $team = Team::create([
            'name' => 'Club Atlético River Plate',
            'api_name' => null,
            'short_name' => 'CAR',
            'external_id' => 'river-plate-local',
            'type' => 'club',
        ]);

        Http::fake([
            'https://api.football-data.org/v4/competitions/CL/matches*' => Http::response([
                'matches' => [
                    [
                        'id' => 9001,
                        'homeTeam' => ['id' => null, 'name' => 'River Plate Club de Futbol'],
                        'awayTeam' => ['id' => null, 'name' => 'Manchester City FC'],
                        'utcDate' => now()->addDays(5)->toIso8601String(),
                        'status' => 'TIMED',
                        'score' => ['fullTime' => ['home' => null, 'away' => null]],
                        'matchday' => 30
                    ]
                ]
            ], 200)
        ]);

        $this->artisan('app:sync-football-api-team-names', [
            'league' => 'champions-league',
            '--days-ahead' => 10,
        ])->assertExitCode(0);

        $team->refresh();

        $this->assertSame('River Plate Club de Futbol', $team->api_name);
    }
}
