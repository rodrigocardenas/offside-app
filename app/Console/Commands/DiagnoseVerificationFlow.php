<?php

namespace App\Console\Commands;

use App\Models\FootballMatch;
use App\Models\Question;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseVerificationFlow extends Command
{
    protected $signature = 'app:diagnose-verification-flow {--limit=5}';
    protected $description = 'Diagnosticar el flujo de verificación de preguntas';

    public function handle()
    {
        $limit = $this->option('limit');

        $this->info('='.str_repeat('=', 70).'=');
        $this->info('DIAGNÓSTICO DEL FLUJO DE VERIFICACIÓN DE PREGUNTAS');
        $this->info('='.str_repeat('=', 70).'=');

        // 1. Partidos terminados
        $this->newLine();
        $this->line('<fg=cyan>1. PARTIDOS TERMINADOS (últimas 24h)</fg=cyan>');
        $this->line(str_repeat('-', 72));

        $finishedMatches = FootballMatch::whereIn('status', ['Match Finished', 'FINISHED', 'Finished'])
            ->where('date', '>=', now()->subHours(24))
            ->orderBy('date', 'desc')
            ->limit($limit)
            ->get();

        if ($finishedMatches->isEmpty()) {
            $this->warn('❌ No hay partidos terminados en las últimas 24 horas');
        } else {
            foreach ($finishedMatches as $match) {
                $this->line("  ID: {$match->id} | {$match->home_team} vs {$match->away_team}");
                $this->line("  └─ Status: {$match->status}");
                $this->line("  └─ Score: {$match->home_team_score} - {$match->away_team_score}");

                // Decodificar statistics
                $stats = is_string($match->statistics)
                    ? json_decode($match->statistics, true)
                    : $match->statistics;

                $source = $stats['source'] ?? 'UNKNOWN';
                $verified = $stats['verified'] ?? false;
                $this->line("  └─ Statistics Source: {$source}");
                $this->line("  └─ Verified: " . ($verified ? '✓ SÍ' : '✗ NO'));

                // Eventos
                $hasEvents = !empty($match->events);
                $this->line("  └─ Events: " . ($hasEvents ? '✓ SÍ' : '✗ NO'));

                $this->newLine();
            }
        }

        // 2. Preguntas sin verificar de estos partidos
        $this->newLine();
        $this->line('<fg=cyan>2. PREGUNTAS SIN VERIFICAR DE ESTOS PARTIDOS</fg=cyan>');
        $this->line(str_repeat('-', 72));

        $matchIds = $finishedMatches->pluck('id')->toArray();

        if (empty($matchIds)) {
            $this->warn('No hay partidos para revisar');
        } else {
            $pendingQuestions = Question::whereIn('match_id', $matchIds)
                ->whereNull('result_verified_at')
                ->with('football_match', 'options', 'answers')
                ->limit($limit * 2)
                ->get();

            if ($pendingQuestions->isEmpty()) {
                $this->info('✓ Todas las preguntas de estos partidos fueron verificadas');
            } else {
                foreach ($pendingQuestions as $q) {
                    $match = $q->football_match;
                    $this->line("  Q#{$q->id}: {$q->title}");
                    $this->line("  └─ Match: {$match->home_team} vs {$match->away_team} ({$match->status})");
                    $this->line("  └─ Type: {$q->type}");
                    $this->line("  └─ Options: " . $q->options->count());
                    $this->line("  └─ Answers: " . $q->answers->count());
                    $this->line("  └─ Verified At: " . ($q->result_verified_at ?? 'NO'));

                    // Revisar si hay respuestas correctas asignadas
                    $correctOptions = $q->options->where('is_correct', true)->count();
                    $this->line("  └─ Correct Options: {$correctOptions}");

                    // Revisar si hay puntos asignados
                    $totalPoints = $q->answers->sum('points_earned');
                    $this->line("  └─ Total Points Earned: {$totalPoints}");

                    $this->newLine();
                }
            }
        }

        // 3. Revisar estructura de datos de un match específico
        if (!$finishedMatches->isEmpty()) {
            $this->newLine();
            $this->line('<fg=cyan>3. DETALLES PROFUNDOS DEL PRIMER PARTIDO</fg=cyan>');
            $this->line(str_repeat('-', 72));

            $firstMatch = $finishedMatches->first();

            // Statistics
            $stats = is_string($firstMatch->statistics)
                ? json_decode($firstMatch->statistics, true)
                : $firstMatch->statistics;

            $this->line('Statistics JSON:');
            $this->line(json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Events
            if ($firstMatch->events) {
                $events = is_string($firstMatch->events)
                    ? json_decode($firstMatch->events, true)
                    : $firstMatch->events;

                $this->newLine();
                $this->line('Events JSON:');
                if (is_array($events) && count($events) > 0) {
                    $this->line('  Primeros 3 eventos:');
                    foreach (array_slice($events, 0, 3) as $event) {
                        $this->line('  ' . json_encode($event));
                    }
                    $this->line('  Total eventos: ' . count($events));
                } else {
                    $this->line('  ❌ NO HAY EVENTOS O ESTRUCTURA INVÁLIDA');
                }
            }
        }

        // 4. Estadísticas generales
        $this->newLine();
        $this->line('<fg=cyan>4. ESTADÍSTICAS GENERALES</fg=cyan>');
        $this->line(str_repeat('-', 72));

        $totalFinished = FootballMatch::whereIn('status', ['Match Finished', 'FINISHED', 'Finished'])->count();
        $totalQuestions = Question::count();
        $pendingQuestions = Question::whereNull('result_verified_at')->count();
        $verifiedQuestions = Question::whereNotNull('result_verified_at')->count();

        $this->line("Partidos terminados: {$totalFinished}");
        $this->line("Total preguntas: {$totalQuestions}");
        $this->line("Preguntas sin verificar: {$pendingQuestions}");
        $this->line("Preguntas verificadas: {$verifiedQuestions}");

        $this->newLine();
        $this->info('✅ Diagnóstico completado');
    }
}
