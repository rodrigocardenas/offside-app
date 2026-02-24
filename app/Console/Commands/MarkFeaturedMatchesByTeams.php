<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;

class MarkFeaturedMatchesByTeams extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mark-featured-matches-by-teams';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark matches as featured if both teams are featured (big match)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Resetear todos los partidos a no destacados
        FootballMatch::query()->update(['is_featured' => false]);

        // Marcar partidos como destacados si ambos equipos son destacados
        $featuredMatches = FootballMatch::query()
            ->whereHas('homeTeam', function ($q) {
                $q->where('is_featured', true);
            })
            ->whereHas('awayTeam', function ($q) {
                $q->where('is_featured', true);
            })
            ->update(['is_featured' => true]);

        $this->info("✓ Partidos destacados marcados: $featuredMatches");
    }
}
