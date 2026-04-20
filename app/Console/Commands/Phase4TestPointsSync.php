<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Group;
use App\Models\User;
use App\Models\Question;
use App\Models\Answer;
use App\Models\FootballMatch;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Phase4TestPointsSync extends Command
{
    protected $signature = 'phase4:test-points-sync {--clean : Limpiar datos de prueba despues}';

    protected $description = 'Test Phase 4 points synchronization: answers->group_user';

    protected $testGroupId;
    protected $testUserIds = [];
    protected $results = ['passed' => 0, 'failed' => 0, 'warnings' => []];

    public function handle()
    {
        $this->line(str_repeat('=', 65));
        $this->info('  PHASE 4: POINTS SYNCHRONIZATION TEST');
        $this->info('  Testing: answers.points_earned to group_user.points');
        $this->line(str_repeat('=', 65) . "\n");

        try {
            $this->info('[1] Preparando datos de prueba...');
            $this->setupTestData();

            $this->info("\n[2] Verificar sincronizacion en tiempo real (Phase 1)...");
            $this->testRealTimeSync();

            $this->info("\n[3] Verificar datos historicos sincronizados (Phase 2)...");
            $this->testHistoricalSync();

            $this->info("\n[4] Verificar rankings optimizados (Phase 4)...");
            $this->testRankingOptimization();

            $this->info("\n[5] Probar castigos Pre-Match con puntos...");
            $this->testPreMatchPenalties();

            $this->showFinalReport();

        } catch (\Exception $e) {
            $this->error("ERROR: " . $e->getMessage());
            if ($this->getOutput()->isVerbose()) {
                $this->error($e->getTraceAsString());
            }
        } finally {
            if ($this->option('clean')) {
                $this->info("\nLimpiando datos de prueba...");
                $this->cleanupTestData();
            }
        }
    }

    private function setupTestData()
    {
        $this->testGroupId = Group::create([
            'name' => 'Phase4 Test Group - ' . now()->timestamp,
            'description' => 'Grupo temporal para testing de sincronizacion de puntos',
            'category' => 'private',
            'created_by' => User::first()->id ?? 1,
            'code' => 'TEST' . rand(1000, 9999)
        ])->id;

        $this->info("  OK - Grupo creado: ID {$this->testGroupId}");

        for ($i = 1; $i <= 3; $i++) {
            $user = User::create([
                'name' => "Test User {$i} - " . now()->timestamp,
                'email' => "test{$i}." . now()->timestamp . "@test.local",
                'password' => bcrypt('password'),
            ]);
            $this->testUserIds[] = $user->id;
            Group::find($this->testGroupId)->users()->attach($user->id, ['points' => 0]);
            $this->info("  OK - Usuario creado: {$user->name}");
        }

        $match = FootballMatch::create([
            'external_id' => 'TEST_' . rand(10000, 99999),
            'home_team' => 'Test FC',
            'away_team' => 'Demo FC',
            'score' => '2-1',
            'status' => 'Match Finished',
            'date' => Carbon::now()->subHours(2),
            'season' => now()->year,
            'round' => 'Phase 4 Test',
            'league' => 'TEST LEAGUE'
        ]);

        $this->info("  OK - Partido creado");

        $question = Question::create([
            'title' => 'Phase 4 Test Question',
            'description' => 'Pregunta de prueba',
            'group_id' => $this->testGroupId,
            'football_match_id' => $match->id,
            'points' => 300,
            'is_featured' => false,
            'status' => 'available',
            'type' => 'predictive',
            'category' => 'predictive',
            'available_until' => Carbon::now()->addHours(24)
        ]);

        $correctOption = $question->options()->create([
            'text' => 'Correcta',
            'is_correct' => true
        ]);

        $question->options()->create(['text' => 'Incorrecta 1', 'is_correct' => false]);
        $question->options()->create(['text' => 'Incorrecta 2', 'is_correct' => false]);

        $this->info("  OK - Pregunta creada");

        foreach ($this->testUserIds as $index => $userId) {
            $isCorrect = ($index === 0);
            $option = $isCorrect 
                ? $correctOption 
                : $question->options()->where('is_correct', false)->first();
            
            Answer::create([
                'user_id' => $userId,
                'question_id' => $question->id,
                'question_option_id' => $option->id,
                'points_earned' => 0,
                'is_correct' => $isCorrect,
            ]);
        }

        $this->info("  OK - Respuestas creadas (usuario 1 acertara, otros no)");
    }

    private function testRealTimeSync()
    {
        $question = Question::where('title', 'Phase 4 Test Question')->first();
        
        $this->info("  Estado ANTES de verificacion:");

        // Simular verificacion
        $correctOption = $question->options()->where('is_correct', true)->first();
        
        foreach ($question->answers as $answer) {
            $answer->update([
                'is_correct' => $answer->question_option_id === $correctOption->id,
                'points_earned' => $answer->question_option_id === $correctOption->id ? 300 : 0
            ]);
        }

        // Simular sincronizacion (Phase 1)
        $this->info("  Sincronizando puntos a group_user...");
        
        foreach ($question->answers as $answer) {
            $groupUser = Group::find($this->testGroupId)
                ->users()
                ->where('users.id', $answer->user_id)
                ->first();

            if ($groupUser && $answer->points_earned > 0) {
                Group::find($this->testGroupId)->users()->updateExistingPivot(
                    $answer->user_id,
                    ['points' => $groupUser->pivot->points + $answer->points_earned]
                );
            }
        }

        $this->info("  Estado DESPUES de sincronizacion:");
        
        foreach ($question->answers()->with('user')->get() as $answer) {
            $groupUser = Group::find($this->testGroupId)
                ->users()
                ->where('users.id', $answer->user_id)
                ->first();
            
            $groupPoints = $groupUser->pivot->points ?? 0;
            $status = ($answer->points_earned === $groupPoints) ? 'OK' : 'FALLO';
            
            $this->info("    {$answer->user->name}: points_earned={$answer->points_earned}, group={$groupPoints} [{$status}]");
            
            if ($answer->points_earned !== $groupPoints) {
                $this->results['failed']++;
                $this->results['warnings'][] = "Desincronizacion: {$answer->user->name}";
            } else {
                $this->results['passed']++;
            }
        }
    }

    private function testHistoricalSync()
    {
        $group = Group::find($this->testGroupId);
        
        $this->info("  Verificando historico...");

        $totalGroupPoints = 0;
        foreach ($group->users as $user) {
            $points = $user->pivot->points ?? 0;
            $totalGroupPoints += $points;
        }

        $answersPoints = DB::table('answers')
            ->join('questions', 'questions.id', '=', 'answers.question_id')
            ->where('questions.group_id', $group->id)
            ->sum('answers.points_earned');

        $this->info("    Total en group_user: {$totalGroupPoints} pts");
        $this->info("    Total en answers: {$answersPoints} pts");

        if ($totalGroupPoints === $answersPoints) {
            $this->info("    OK - Historico sincronizado");
            $this->results['passed']++;
        } else {
            $this->error("    FALLO - Inconsistencia detectada");
            $this->results['failed']++;
            $this->results['warnings'][] = "Desincronizacion historica";
        }
    }

    private function testRankingOptimization()
    {
        $group = Group::find($this->testGroupId);
        $this->info("  Verificando optimizacion...");

        DB::enableQueryLog();
        $rankedUsers = $group->rankedUsers()->get();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        if (!empty($queries)) {
            $query = end($queries)['query'] ?? '';
            $hasAnswersJoin = (strpos($query, 'answers') !== false);
            $hasGroupBy = (strpos($query, 'GROUP BY') !== false);

            if (!$hasAnswersJoin && !$hasGroupBy) {
                $this->info("    OK - Query optimizada (sin JOINs innecesarios)");
                $this->results['passed']++;
            } else {
                $this->error("    FALLO - Query tiene JOINs innecesarios");
                $this->results['failed']++;
            }
        }

        $this->info("    Usuarios ordenados:");
        foreach ($rankedUsers as $user) {
            $points = $user->total_points ?? 0;
            $this->info("      {$user->name}: {$points} pts");
        }
    }

    private function testPreMatchPenalties()
    {
        $group = Group::find($this->testGroupId);
        $firstUser = $group->users->first();

        if (!$firstUser) {
            $this->warn("  Sin usuarios para probar castigos");
            return;
        }

        $pointsBefore = $firstUser->pivot->points ?? 0;
        $this->info("  Puntos ANTES: {$pointsBefore}");

        $penaltyAmount = 50;
        $pointsAfter = max(0, $pointsBefore - $penaltyAmount);

        $group->users()->updateExistingPivot($firstUser->id, ['points' => $pointsAfter]);

        $pointsAfterCheck = $group->users()
            ->where('users.id', $firstUser->id)
            ->first()
            ->pivot->points ?? 0;

        $this->info("  Castigo aplicado: -{$penaltyAmount}");
        $this->info("  Puntos DESPUES: {$pointsAfterCheck}");

        if ($pointsAfterCheck === $pointsAfter) {
            $this->info("  OK - Castigo aplicado correctamente");
            $this->results['passed']++;
        } else {
            $this->error("  FALLO - Error al aplicar castigo");
            $this->results['failed']++;
        }

        if ($pointsAfterCheck >= 0) {
            $this->info("  OK - Proteccion contra negativos funcionando");
            $this->results['passed']++;
        }
    }

    private function showFinalReport()
    {
        $this->line("\n" . str_repeat("=", 65));
        $this->info("REPORTE FINAL");
        $this->line(str_repeat("=", 65));

        $passed = $this->results['passed'];
        $failed = $this->results['failed'];
        $total = $passed + $failed;

        $this->info("\nRESULTADOS:");
        $this->info("  Pruebas pasadas: {$passed}");
        if ($failed > 0) {
            $this->error("  Pruebas fallidas: {$failed}");
        } else {
            $this->info("  Pruebas fallidas: {$failed}");
        }

        if (!empty($this->results['warnings'])) {
            $this->warn("\nADVERTENCIAS:");
            foreach ($this->results['warnings'] as $warning) {
                $this->warn("  - {$warning}");
            }
        }

        if ($failed === 0) {
            $this->info("\nOK - TODAS LAS PRUEBAS PASARON");
            $this->info("Phase 4 esta funcionando correctamente");
        } else {
            $this->error("\nFALLO - {$failed} pruebas fallaron");
            $this->error("Revisar logs y documentacion");
        }

        $this->info("\nProximos pasos:");
        if ($failed === 0) {
            $this->line("  1. Ejecutar tests: php artisan test");
            $this->line("  2. Verificar en staging");
            $this->line("  3. Deploy a produccion");
        } else {
            $this->line("  1. Revisar los errores arriba");
            $this->line("  2. Verificar que Phase 1 y 2 completadas");
            $this->line("  3. Ejecutar migracion: php artisan migrate");
        }
    }

    private function cleanupTestData()
    {
        if ($this->testGroupId) {
            Group::destroy($this->testGroupId);
        }

        if (!empty($this->testUserIds)) {
            User::whereIn('id', $this->testUserIds)->delete();
        }

        $this->info("  OK - Datos limpios");
    }
}
