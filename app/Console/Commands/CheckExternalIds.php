<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;

class CheckExternalIds extends Command
{
    protected $signature = 'app:check-external-ids';
    protected $description = 'Check external IDs of recent matches';

    public function handle(): int
    {
        $this->info("Checking external IDs of finished matches:\n");

        $matches = FootballMatch::whereIn('status', ['Match Finished', 'FINISHED'])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        foreach ($matches as $match) {
            $external = $match->external_id;
            $isNumeric = is_numeric($external);
            $this->line("ID {$match->id}: {$match->home_team} vs {$match->away_team}");
            $this->line("  External ID: $external | Numeric: " . ($isNumeric ? 'YES' : 'NO'));
            $this->line("  League: {$match->league}");
            $this->line("  Score: {$match->score}");
            $this->line("");
        }

        return 0;
    }
}
