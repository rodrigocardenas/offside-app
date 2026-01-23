<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;

class VerifyRecovery extends Command
{
    protected $signature = 'app:verify-recovery {match_id=448}';
    protected $description = 'Verify that recovery populated events and statistics';

    public function handle(): int
    {
        $matchId = $this->argument('match_id');
        $match = FootballMatch::find($matchId);

        if (!$match) {
            $this->error("Match not found");
            return 1;
        }

        $this->line("\n╔════════════════════════════════════════════════════════╗");
        $this->line("║ Match: {$match->home_team} vs {$match->away_team}");
        $this->line("║ Score: {$match->score}");
        $this->line("╚════════════════════════════════════════════════════════╝\n");

        if ($match->events) {
            $events = json_decode($match->events, true);
            $this->info("✓ EVENTS FOUND: " . count($events) . " total");
            foreach (array_slice($events, 0, 5) as $event) {
                $minute = $event['minute'] ?? 'N/A';
                $type = $event['type'] ?? 'N/A';
                $player = $event['player'] ?? 'N/A';
                $team = $event['team'] ?? 'N/A';
                $this->line("  • {$minute}' | {$type} | {$player} ({$team})");
            }
            if (count($events) > 5) {
                $this->line("  ... and " . (count($events) - 5) . " more");
            }
        } else {
            $this->warn("✗ No events found");
        }

        if ($match->statistics) {
            $stats = json_decode($match->statistics, true);
            $this->info("\n✓ STATISTICS FOUND:");
            foreach ($stats as $key => $value) {
                if (!is_array($value)) {
                    $this->line("  • {$key}: {$value}");
                }
            }
        } else {
            $this->warn("✗ No statistics found");
        }

        $this->line("");
        return 0;
    }
}
