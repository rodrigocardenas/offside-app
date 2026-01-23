<?php

namespace App\Console\Commands;

use App\Models\Question;
use Illuminate\Console\Command;

class DebugVerificationTimeline extends Command
{
    protected $signature = 'app:debug-verification-timeline';
    protected $description = 'Show verification timeline and identify gaps';

    public function handle()
    {
        $this->line("\n╔════════════════════════════════════════════════════════════╗");
        $this->line("║ Línea de Tiempo de Verificación                            ║");
        $this->line("╚════════════════════════════════════════════════════════════╝\n");

        // Group questions by verification date
        $verified = \DB::table('questions')
            ->whereNotNull('result_verified_at')
            ->selectRaw('DATE(result_verified_at) as date, COUNT(*) as count')
            ->groupByRaw('DATE(result_verified_at)')
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        $this->line("Preguntas verificadas por fecha:");
        foreach ($verified as $row) {
            $this->line("  {$row->date}: {$row->count} preguntas ✓");
        }

        // Check pending questions by match date
        $this->line("\n\nPreguntas pendientes por fecha de partido:");
        $pending = \DB::table('questions')
            ->join('football_matches', 'questions.match_id', '=', 'football_matches.id')
            ->whereNull('questions.result_verified_at')
            ->selectRaw('DATE(football_matches.date) as match_date, COUNT(*) as count, COUNT(DISTINCT football_matches.id) as matches')
            ->groupByRaw('DATE(football_matches.date)')
            ->orderByDesc('match_date')
            ->limit(10)
            ->get();

        if ($pending->isEmpty()) {
            $this->line("  No hay preguntas pendientes 🎉");
        } else {
            foreach ($pending as $row) {
                $this->line("  {$row->match_date}: {$row->count} preguntas en {$row->matches} partidos");
            }
        }

        // Check last verification attempt
        $this->line("\n\nÚltimos intentos de verificación:");
        $attempts = \DB::table('football_matches')
            ->whereNotNull('last_verification_attempt_at')
            ->orderByDesc('last_verification_attempt_at')
            ->limit(5)
            ->get(['id', 'home_team', 'away_team', 'status', 'last_verification_attempt_at']);

        foreach ($attempts as $match) {
            $this->line("  Match {$match->id}: {$match->home_team} vs {$match->away_team} - {$match->last_verification_attempt_at}");
        }

        // Check failed jobs or errors
        $this->line("\n\nÚltimas preguntas verificadas:");
        $lastVerified = Question::whereNotNull('result_verified_at')
            ->with('football_match')
            ->orderByDesc('result_verified_at')
            ->limit(5)
            ->get();

        foreach ($lastVerified as $q) {
            $matchInfo = $q->football_match ? "{$q->football_match->home_team} vs {$q->football_match->away_team}" : "Match unknown";
            $this->line("  Q{$q->id}: {$q->title}");
            $this->line("    Match: {$matchInfo}");
            $this->line("    Verificada: {$q->result_verified_at}");
        }

        $this->line("\n╔════════════════════════════════════════════════════════════╗");
        $this->line("║ Análisis completado                                        ║");
        $this->line("╚════════════════════════════════════════════════════════════╝\n");
    }
}
