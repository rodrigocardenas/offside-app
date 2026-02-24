<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use Illuminate\Support\Facades\DB;

class PopulateCompetitionTeam extends Command
{
    protected $signature = 'app:populate-competition-team';
    protected $description = 'Populate the competition_team pivot table from football_matches';

    public function handle()
    {
        $this->info('Starting to populate competition_team table...');

        try {
            // Get all unique combinations of competition_id and team_id from football_matches
            $homeTeams = DB::table('football_matches')
                ->whereNotNull('competition_id')
                ->whereNotNull('home_team_id')
                ->select(['competition_id', 'home_team_id as team_id'])
                ->distinct();

            $awayTeams = DB::table('football_matches')
                ->whereNotNull('competition_id')
                ->whereNotNull('away_team_id')
                ->select(['competition_id', 'away_team_id as team_id'])
                ->distinct();

            $teamCompetitions = $homeTeams->union($awayTeams)->get();

            $bar = $this->output->createProgressBar(count($teamCompetitions));
            $bar->start();

            $inserted = 0;
            $skipped = 0;

            foreach ($teamCompetitions as $tc) {
                $exists = DB::table('competition_team')
                    ->where('competition_id', $tc->competition_id)
                    ->where('team_id', $tc->team_id)
                    ->exists();

                if (!$exists) {
                    try {
                        DB::table('competition_team')->insert([
                            'competition_id' => $tc->competition_id,
                            'team_id' => $tc->team_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $inserted++;
                    } catch (\Exception $e) {
                        $skipped++;
                    }
                } else {
                    $skipped++;
                }

                $bar->advance();
            }

            $bar->finish();

            $this->line('');
            $this->info("✓ Competition-Team relationships populated!");
            $this->line("  Inserted: {$inserted}");
            $this->line("  Skipped (already existed): {$skipped}");

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
