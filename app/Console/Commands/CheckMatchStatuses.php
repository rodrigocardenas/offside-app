<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;

class CheckMatchStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:match-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all unique match statuses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("=== ESTADOS DE PARTIDOS ===\n");

        $statuses = FootballMatch::select('status')->distinct()->pluck('status');

        foreach ($statuses as $status) {
            $count = FootballMatch::where('status', $status)->count();
            $this->info("Estado: '$status' - $count partidos");
        }

        $this->info("\n=== PARTIDOS RECIENTES ===");
        $recentMatches = FootballMatch::where('date', '>=', now()->subDays(7))
            ->orderBy('date', 'desc')
            ->take(10)
            ->get();

        foreach ($recentMatches as $match) {
            $this->info("ID: {$match->id}, {$match->home_team} vs {$match->away_team}, Estado: '{$match->status}', Fecha: {$match->date}");
        }
    }
}
