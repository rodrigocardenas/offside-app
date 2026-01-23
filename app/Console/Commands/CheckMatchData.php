<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;

class CheckMatchData extends Command
{
    protected $signature = 'app:check-match-data {match_id}';
    protected $description = 'Check all data of a match';

    public function handle(): int
    {
        $matchId = $this->argument('match_id');
        $match = FootballMatch::find($matchId);

        if (!$match) {
            $this->error("Match not found");
            return 1;
        }

        $this->info("Match ID: $matchId");
        $this->line("Home: {$match->home_team}");
        $this->line("Away: {$match->away_team}");
        $this->line("Date: {$match->date}");
        $this->line("Score: '{$match->score}'");
        $this->line("Home Score: {$match->home_team_score}");
        $this->line("Away Score: {$match->away_team_score}");
        $this->line("Status: {$match->status}");
        $this->line("External ID: {$match->external_id}");
        $this->line("League: {$match->league}");
        $this->line("Events: " . (strlen($match->events) > 0 ? strlen($match->events) . " bytes" : "EMPTY"));
        $this->line("Statistics: " . (strlen($match->statistics) > 0 ? strlen($match->statistics) . " bytes" : "EMPTY"));

        return 0;
    }
}
