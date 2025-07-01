<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FootballMatch;
use App\Models\Question;
use App\Models\Group;

class DiagnoseJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diagnose:job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose why ProcessRecentlyFinishedMatchesJob is not working';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("=== DIAGNÓSTICO DEL JOB ProcessRecentlyFinishedMatchesJob ===\n");

        // 1. Verificar partidos que deberían haber terminado
        $this->info("1. Verificando partidos que deberían haber terminado:");
        $finishedMatches = FootballMatch::whereNotIn('status', ['FINISHED', 'Match Finished'])
            ->where('date', '<=', now()->subHours(2))
            ->where('date', '>=', now()->subHours(100))
            ->get();

        $this->info("   Partidos encontrados: " . $finishedMatches->count());

        if ($finishedMatches->count() > 0) {
            foreach ($finishedMatches as $match) {
                $this->info("   - ID: {$match->id}, {$match->home_team} vs {$match->away_team}, Estado: {$match->status}, Fecha: {$match->date}");
            }
        }

        // 2. Verificar preguntas pendientes de verificación
        $this->info("\n2. Verificando preguntas pendientes de verificación:");
        $pendingQuestions = Question::whereNull('result_verified_at')
            ->whereHas('football_match', function($query) {
                $query->whereIn('status', ['FINISHED', 'Match Finished']);
            })
            ->get();

        $this->info("   Preguntas pendientes: " . $pendingQuestions->count());

        // 3. Verificar grupos para preguntas predictivas
        $this->info("\n3. Verificando grupos para preguntas predictivas:");
        $groups = Group::with('competition')
            ->whereNotNull('competition_id')
            ->get();

        $this->info("   Grupos con competición: " . $groups->count());

        foreach ($groups as $group) {
            $activeCount = $group->questions()
                ->where('type', 'predictive')
                ->where('available_until', '>', now())
                ->count();

            $this->info("   - Grupo {$group->id}: {$activeCount} preguntas activas");
        }

        // 4. Verificar configuración de logs
        $this->info("\n4. Verificando configuración de logs:");
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            $this->info("   Log file existe: " . $logPath);
            $this->info("   Tamaño del log: " . filesize($logPath) . " bytes");
        } else {
            $this->error("   Log file no existe!");
        }

        $this->info("\n=== FIN DEL DIAGNÓSTICO ===");
    }
}
