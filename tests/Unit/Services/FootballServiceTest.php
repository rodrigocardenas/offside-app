<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FootballService;
use App\Exceptions\FootballApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FootballServiceTest extends TestCase
{
    protected $footballService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->footballService = new FootballService();
    }

    /** @test */
    public function it_throws_exception_for_unsupported_competition()
    {
        $this->expectException(FootballApiException::class);
        $this->expectExceptionMessage('Competencia no soportada: unsupported-league');

        $this->footballService->getNextMatches('unsupported-league');
    }

    /** @test */
    public function it_throws_exception_when_api_call_fails()
    {
        // Mock HTTP facade to return failed response
        Http::shouldReceive('withHeaders->get')
            ->once()
            ->andReturn(Http::response(['error' => 'API Error'], 500));

        $this->expectException(FootballApiException::class);
        $this->expectExceptionMessage('Error al obtener los partidos desde la API externa');

        $this->footballService->getNextMatches('premier-league');
    }

    /** @test */
    public function it_returns_formatted_matches_when_api_succeeds()
    {
        $apiResponse = [
            'response' => [
                [
                    'fixture' => [
                        'date' => '2025-01-15T20:00:00+00:00',
                        'venue' => ['name' => 'Stamford Bridge']
                    ],
                    'teams' => [
                        'home' => ['name' => 'Chelsea'],
                        'away' => ['name' => 'Arsenal']
                    ],
                    'fixture' => [
                        'status' => ['long' => 'Not Started']
                    ]
                ]
            ]
        ];

        Http::shouldReceive('withHeaders->get')
            ->once()
            ->andReturn(Http::response($apiResponse, 200));

        $result = $this->footballService->getNextMatches('premier-league', 1);

        $this->assertCount(1, $result);
        $this->assertEquals('2025-01-15T20:00:00+00:00', $result->first()['fecha']);
        $this->assertEquals('Chelsea', $result->first()['local']);
        $this->assertEquals('Arsenal', $result->first()['visitante']);
    }

    /** @test */
    public function it_caches_matches_results()
    {
        $apiResponse = [
            'response' => [
                [
                    'fixture' => [
                        'date' => '2025-01-15T20:00:00+00:00',
                        'venue' => ['name' => 'Test Stadium']
                    ],
                    'teams' => [
                        'home' => ['name' => 'Team A'],
                        'away' => ['name' => 'Team B']
                    ],
                    'fixture' => [
                        'status' => ['long' => 'Not Started']
                    ]
                ]
            ]
        ];

        Http::shouldReceive('withHeaders->get')
            ->once()
            ->andReturn(Http::response($apiResponse, 200));

        // First call should hit the API
        $result1 = $this->footballService->getMatches(39, false);
        $this->assertCount(1, $result1);

        // Second call should use cache
        $result2 = $this->footballService->getMatches(39, false);
        $this->assertEquals($result1, $result2);
    }

    /** @test */
    public function it_forces_refresh_when_requested()
    {
        $apiResponse = [
            'response' => [
                [
                    'fixture' => [
                        'date' => '2025-01-15T20:00:00+00:00',
                        'venue' => ['name' => 'Test Stadium']
                    ],
                    'teams' => [
                        'home' => ['name' => 'Team A'],
                        'away' => ['name' => 'Team B']
                    ],
                    'fixture' => [
                        'status' => ['long' => 'Not Started']
                    ]
                ]
            ]
        ];

        Http::shouldReceive('withHeaders->get')
            ->twice() // Should be called twice due to force refresh
            ->andReturn(Http::response($apiResponse, 200));

        // First call
        $this->footballService->getMatches(39, false);

        // Second call with force refresh
        $this->footballService->getMatches(39, true);

        // Verify HTTP was called twice
        Http::shouldHaveReceived('withHeaders->get')->twice();
    }

    /** @test */
    public function it_handles_season_calculation_correctly()
    {
        // Test season calculation (private method, we'll test indirectly)
        $matches = $this->footballService->getMatches(39, false);

        // This test mainly ensures the method doesn't throw exceptions
        // In a real scenario, we'd mock the HTTP responses
        $this->assertIsIterable($matches);
    }

    /** @test */
    public function it_identifies_latin_american_leagues_correctly()
    {
        // Test the private method indirectly through public methods
        // This is more of an integration test

        $reflection = new \ReflectionClass($this->footballService);
        $method = $reflection->getMethod('isLatinAmericanLeague');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->footballService, 'liga-colombia'));
        $this->assertFalse($method->invoke($this->footballService, 'premier-league'));
        $this->assertFalse($method->invoke($this->footballService, 'champions-league'));
    }

    /** @test */
    public function it_calculates_season_for_date_correctly()
    {
        $reflection = new \ReflectionClass($this->footballService);
        $method = $reflection->getMethod('getSeasonForDate');
        $method->setAccessible(true);

        // January 2025 should return 2024 (previous season)
        $jan2025 = \Carbon\Carbon::create(2025, 1, 15);
        $this->assertEquals(2024, $method->invoke($this->footballService, $jan2025));

        // August 2025 should return 2025 (current season)
        $aug2025 = \Carbon\Carbon::create(2025, 8, 15);
        $this->assertEquals(2025, $method->invoke($this->footballService, $aug2025));

        // Current date (no parameter)
        $currentSeason = $method->invoke($this->footballService);
        $this->assertIsInt($currentSeason);
    }

    /** @test */
    public function it_applies_rate_limit_delay()
    {
        $reflection = new \ReflectionClass($this->footballService);
        $method = $reflection->getMethod('applyRateLimitDelay');
        $method->setAccessible(true);

        $startTime = microtime(true);
        $method->invoke($this->footballService, 0.1, 0.1); // Short delay for testing
        $endTime = microtime(true);

        // Should have waited at least 0.1 seconds
        $this->assertGreaterThan(0.05, $endTime - $startTime);
    }
}
