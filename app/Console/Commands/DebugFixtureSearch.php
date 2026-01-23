<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use App\Services\FootballService;

class DebugFixtureSearch extends Command
{
    protected $signature = 'app:debug-fixture-search';
    protected $description = 'Debug fixture search for specific teams';
    protected $footballService;

    public function __construct(FootballService $footballService)
    {
        parent::__construct();
        $this->footballService = $footballService;
    }

    public function handle(): int
    {
        // Test: Manchester Utd vs Manchester City
        $this->line("Test 1: Manchester Utd vs Manchester City");
        $result = $this->footballService->buscarFixtureId(
            'premier-league',
            2025,
            'Manchester Utd.',
            'Manchester City',
            '2025-01-20'
        );
        $this->line("Result: " . ($result ?? 'NOT FOUND'));
        
        // Try variations
        $result2 = $this->footballService->buscarFixtureId(
            'premier-league',
            2025,
            'Manchester United',
            'Manchester City',
            '2025-01-20'
        );
        $this->line("Result (Manchester United): " . ($result2 ?? 'NOT FOUND'));
        
        $this->line("\nTest 2: Chelsea vs Arsenal");
        $result3 = $this->footballService->buscarFixtureId(
            'league-cup',
            2025,
            'Chelsea',
            'Arsenal',
            '2025-01-21'
        );
        $this->line("Result: " . ($result3 ?? 'NOT FOUND'));
        
        $this->line("\nTest 3: Borussia Dortmund vs Werder Bremen");
        $result4 = $this->footballService->buscarFixtureId(
            'bundesliga',
            2025,
            'Borussia Dortmund',
            'Werder Bremen',
            '2025-01-20'
        );
        $this->line("Result: " . ($result4 ?? 'NOT FOUND'));

        return 0;
    }
}
