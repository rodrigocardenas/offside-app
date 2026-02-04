<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;

class CheckEventStructure extends Command
{
    protected $signature = 'app:check-event-structure {match_id}';
    protected $description = 'Check the structure of events for a match';

    public function handle()
    {
        $matchId = $this->argument('match_id');
        $match = FootballMatch::find($matchId);

        if (!$match) {
            $this->error("Match $matchId not found");
            return;
        }

        $this->info("Match: {$match->home_team} vs {$match->away_team}");
        $this->info("Match ID: {$match->id}");

        if (!$match->events) {
            $this->warn("No events found");
            return;
        }

        $events = is_string($match->events) ? json_decode($match->events, true) : $match->events;

        if (!is_array($events)) {
            $this->error("Events is not an array");
            return;
        }

        $this->info("Total events: " . count($events));
        
        // Get all event types
        $types = array_values(array_unique(array_map(fn($e) => $e['type'] ?? 'unknown', $events)));
        $this->info("Event types found: " . json_encode($types));

        if (count($events) > 0) {
            $this->info("\n=== First Event Structure ===");
            $firstEvent = $events[0];
            $this->line(json_encode($firstEvent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // Check for GOAL events
            $goalEvents = array_filter($events, fn($e) => ($e['type'] ?? '') === 'GOAL');
            if (!empty($goalEvents)) {
                $this->info("\n=== First GOAL Event ===");
                $firstGoal = array_values($goalEvents)[0];
                $this->line(json_encode($firstGoal, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                
                if (isset($firstGoal['detail'])) {
                    $this->info("✓ 'detail' field exists: " . $firstGoal['detail']);
                } else {
                    $this->warn("✗ 'detail' field MISSING");
                }
            }
        }
    }
}
