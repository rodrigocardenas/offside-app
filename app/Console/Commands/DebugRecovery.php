<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use App\Services\FootballService;

class DebugRecovery extends Command
{
    protected $signature = 'app:debug-recovery {--days=30}';
    protected $description = 'Debug recovery issues';
    protected $footballService;

    public function __construct(FootballService $footballService)
    {
        parent::__construct();
        $this->footballService = $footballService;
    }

    public function handle(): int
    {
        $days = $this->option('days');

        $matches = FootballMatch::whereIn('status', ['Not Started', 'Scheduled', 'In Play', 'Match Finished'])
            ->where('date', '<=', now()->subHours(2))
            ->where('date', '>=', now()->subDays($days))
            ->where('external_id', '!=', '')
            ->where('external_id', '!=', null)
            ->orderBy('date', 'desc')
            ->get();

        $this->line("Total matches found: " . count($matches));
        $this->line("");

        foreach ($matches as $m) {
            $this->line("ID: {$m->id} | {$m->home_team} vs {$m->away_team}");
            $this->line("  External ID: {$m->external_id}");
            $this->line("  Current: {$m->score} | {$m->status}");
            
            $fixture = $this->footballService->obtenerFixtureDirecto($m->external_id);
            
            if ($fixture) {
                $homeScore = $fixture['goals']['home'] ?? 'N/A';
                $awayScore = $fixture['goals']['away'] ?? 'N/A';
                $this->line("  ✓ API: {$homeScore}-{$awayScore}");
            } else {
                $this->line("  ✗ FAILED");
            }
            $this->line("");
        }

        return 0;
    }
}
