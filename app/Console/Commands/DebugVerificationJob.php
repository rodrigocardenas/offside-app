<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;

class DebugVerificationJob extends Command
{
    protected $signature = 'app:debug-verification';
    protected $description = 'Debug verification job to see which matches would be processed';

    public function handle(): int
    {
        $this->info("╔════════════════════════════════════════════════════════╗");
        $this->info("║ DEBUG: Verification Job Analysis");
        $this->info("╚════════════════════════════════════════════════════════╝\n");

        // Check total matches
        $total = FootballMatch::count();
        $this->line("Total matches in DB: $total");

        // Check matches with status finished
        $finishedCount = FootballMatch::whereIn('status', ['Match Finished', 'FINISHED', 'Finished'])->count();
        $this->line("Matches with status 'Match Finished', 'FINISHED', or 'Finished': $finishedCount");

        // Check all unique statuses
        $statuses = FootballMatch::distinct('status')->pluck('status');
        $this->line("\nAll unique statuses in DB:");
        foreach ($statuses as $status) {
            $count = FootballMatch::where('status', $status)->count();
            $this->line("  • $status: $count");
        }

        // Check matches with pending questions
        $windowStart = now()->subHours(72);
        $withQuestions = FootballMatch::whereIn('status', ['Match Finished', 'FINISHED', 'Finished'])
            ->where('date', '>=', $windowStart)
            ->whereHas('questions', function ($query) {
                $query->whereNull('result_verified_at');
            })
            ->count();

        $this->line("\nMatches finished + has pending questions (last 72 hours): $withQuestions");

        // Show first 5 candidates
        $candidates = FootballMatch::whereIn('status', ['Match Finished', 'FINISHED'])
            ->where('date', '>=', $windowStart)
            ->whereHas('questions', function ($query) {
                $query->whereNull('result_verified_at');
            })
            ->limit(5)
            ->get();

        if ($candidates->count() > 0) {
            $this->line("\nFirst candidates for verification:");
            foreach ($candidates as $match) {
                $pendingQuestions = $match->questions()->whereNull('result_verified_at')->count();
                $lastAttempt = $match->last_verification_attempt_at
                    ? $match->last_verification_attempt_at->format('Y-m-d H:i:s')
                    : 'Never';
                $this->line("  ID {$match->id}: {$match->home_team} vs {$match->away_team}");
                $this->line("    Status: {$match->status}");
                $this->line("    Pending Questions: $pendingQuestions");
                $this->line("    Last Attempt: $lastAttempt");
            }
        } else {
            $this->warn("\n✗ No candidates found for verification!");
        }

        // Check for matches without questions
        $noQuestions = FootballMatch::whereIn('status', ['Match Finished', 'FINISHED'])
            ->where('date', '>=', $windowStart)
            ->doesntHave('questions')
            ->count();

        $this->line("\nMatches finished but NO questions attached: $noQuestions");

        return 0;
    }
}
