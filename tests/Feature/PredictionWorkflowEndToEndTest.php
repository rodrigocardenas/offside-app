<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\FootballMatch;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Answer;
use App\Models\Group;
use App\Models\User;
use App\Models\Competition;
use App\Jobs\VerifyQuestionResultsJob;
use App\Jobs\UpdateAnswersPoints;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PredictionWorkflowEndToEndTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test completo del flujo: Fixtures → Preguntas → Evaluación → Puntos
     *
     * Este test simula el ciclo diario completo:
     * 1. Se descargan fixtures de Football-Data.org
     * 2. Se crean preguntas cuando usuario abre grupo
     * 3. Usuario responde las preguntas
     * 4. Partido termina y se descarga resultado
     * 5. Job verifica resultados y evalúa respuestas
     * 6. Puntos se calculan correctamente
     */
    public function test_complete_prediction_workflow(): void
    {
        // ===== PASO 1: DESCARGAR FIXTURES =====
        Http::fake([
            'https://api.football-data.org/v4/competitions/PL/matches*' => Http::response([
                'matches' => [
                    [
                        'id' => 10001,
                        'homeTeam' => ['id' => 1, 'name' => 'Arsenal'],
                        'awayTeam' => ['id' => 2, 'name' => 'Liverpool'],
                        'utcDate' => now()->addDays(2)->toIso8601String(),
                        'status' => 'SCHEDULED',
                        'score' => ['fullTime' => ['home' => null, 'away' => null]],
                        'matchday' => 25
                    ]
                ]
            ], 200)
        ]);

        // Ejecutar comando de descarga de fixtures
        $this->artisan('app:update-football-data', [
            'league' => 'premier-league',
            '--days-ahead' => 7
        ])->assertExitCode(0);

        // Verificar que el partido se descargó
        $this->assertDatabaseHas('football_matches', [
            'external_id' => 10001,
            'home_team' => 'Arsenal',
            'status' => 'SCHEDULED'
        ]);

        $match = FootballMatch::where('external_id', 10001)->first();
        $this->assertNotNull($match);

        // ===== PASO 2: CREAR GRUPO Y USUARIO =====
        $user = User::factory()->create(['name' => 'Test User']);
        $group = Group::factory()->create(['name' => 'Test Group']);
        $group->users()->attach($user);

        // ===== PASO 3: GENERAR PREGUNTAS =====
        $questionTypes = [
            'resultado' => '¿Cuál será el resultado?',
            'primer_gol' => '¿Quién anotará el primer gol?',
            'ambos' => '¿Ambos equipos anotarán?'
        ];

        $questions = [];
        $questionIdx = 0;

        foreach ($questionTypes as $type => $title) {
            $question = Question::create([
                'title' => $title,
                'type' => 'predictive',
                'match_id' => $match->id,
                'group_id' => $group->id,
                'points' => 300,
                'available_until' => now()->addHours(24)
            ]);

            // Crear opciones según tipo
            if ($type === 'resultado') {
                QuestionOption::create(['question_id' => $question->id, 'text' => 'Victoria Arsenal']);
                QuestionOption::create(['question_id' => $question->id, 'text' => 'Victoria Liverpool']);
                QuestionOption::create(['question_id' => $question->id, 'text' => 'Empate']);
            } elseif ($type === 'primer_gol') {
                QuestionOption::create(['question_id' => $question->id, 'text' => 'Arsenal']);
                QuestionOption::create(['question_id' => $question->id, 'text' => 'Liverpool']);
                QuestionOption::create(['question_id' => $question->id, 'text' => 'Ninguno']);
            } elseif ($type === 'ambos') {
                QuestionOption::create(['question_id' => $question->id, 'text' => 'Sí']);
                QuestionOption::create(['question_id' => $question->id, 'text' => 'No']);
            }

            $questions[$type] = $question->refresh();
            $questionIdx++;
        }

        $this->assertCount(3, $questions);

        // ===== PASO 4: USUARIO RESPONDE LAS PREGUNTAS =====
        // Respuesta 1: Victoria Arsenal (CORRECTA)
        Answer::create([
            'user_id' => $user->id,
            'question_id' => $questions['resultado']->id,
            'question_option_id' => $questions['resultado']->options[0]->id,
            'is_correct' => false // Todavía no verificada
        ]);

        // Respuesta 2: Primer gol Liverpool (INCORRECTA - será Arsenal)
        Answer::create([
            'user_id' => $user->id,
            'question_id' => $questions['primer_gol']->id,
            'question_option_id' => $questions['primer_gol']->options[1]->id,
            'is_correct' => false
        ]);

        // Respuesta 3: Ambos equipos anotan (CORRECTA)
        Answer::create([
            'user_id' => $user->id,
            'question_id' => $questions['ambos']->id,
            'question_option_id' => $questions['ambos']->options[0]->id,
            'is_correct' => false
        ]);

        $this->assertEquals(3, Answer::count());

        // ===== PASO 5: PARTIDO TERMINA - SE DESCARGA RESULTADO =====
        $match->update([
            'status' => 'FINISHED',
            'home_team_score' => 2,
            'away_team_score' => 1,
            'events' => json_encode([
                ['type' => 'GOAL', 'team' => 'HOME', 'minute' => 15], // Arsenal anota (primer gol)
                ['type' => 'GOAL', 'team' => 'AWAY', 'minute' => 35], // Liverpool anota (ambos)
                ['type' => 'GOAL', 'team' => 'HOME', 'minute' => 70]  // Arsenal anota otra
            ])
        ]);

        // ===== PASO 6: VERIFICAR RESULTADOS =====
        VerifyQuestionResultsJob::dispatch();

        // Verificar que todas las preguntas fueron verificadas
        $questions['resultado']->refresh();
        $questions['primer_gol']->refresh();
        $questions['ambos']->refresh();

        $this->assertNotNull($questions['resultado']->result_verified_at);
        $this->assertNotNull($questions['primer_gol']->result_verified_at);
        $this->assertNotNull($questions['ambos']->result_verified_at);

        // ===== PASO 7: VALIDAR RESPUESTAS =====
        $answers = Answer::where('user_id', $user->id)->get();

        // Respuesta 1: Victoria Arsenal - CORRECTA ✅
        $answer1 = $answers->where('question_id', $questions['resultado']->id)->first();
        $this->assertTrue($answer1->is_correct);
        $this->assertEquals(300, $answer1->points_earned);

        // Respuesta 2: Primer gol Liverpool - INCORRECTA ❌
        $answer2 = $answers->where('question_id', $questions['primer_gol']->id)->first();
        $this->assertFalse($answer2->is_correct);
        $this->assertEquals(0, $answer2->points_earned);

        // Respuesta 3: Ambos anotan Sí - CORRECTA ✅
        $answer3 = $answers->where('question_id', $questions['ambos']->id)->first();
        $this->assertTrue($answer3->is_correct);
        $this->assertEquals(300, $answer3->points_earned);

        // ===== PASO 8: VERIFICAR TOTAL DE PUNTOS =====
        $totalPoints = Answer::where('user_id', $user->id)->sum('points_earned');
        $this->assertEquals(600, $totalPoints); // 300 + 0 + 300

        // ===== PASO 9: VERIFICAR LOGS Y AUDITORÍA =====
        // El Job debe haber actualizado correctamente todas las opciones
        foreach ($questions as $question) {
            $question->refresh();

            // Al menos una opción debe estar marcada como correcta
            $correctCount = $question->options->where('is_correct', true)->count();
            $this->assertGreaterThan(0, $correctCount);
        }
    }

    /**
     * Test que el sistema maneja múltiples usuarios correctamente
     */
    public function test_multiple_users_get_correct_points(): void
    {
        $match = FootballMatch::create([
            'external_id' => 20001,
            'home_team' => 'Manchester',
            'away_team' => 'Chelsea',
            'date' => now(),
            'status' => 'FINISHED',
            'home_team_score' => 3,
            'away_team_score' => 2,
            'league' => 'PL',
            'events' => json_encode([
                ['type' => 'GOAL', 'team' => 'HOME', 'minute' => 10],
                ['type' => 'GOAL', 'team' => 'AWAY', 'minute' => 20]
            ])
        ]);

        $group = Group::factory()->create();
        $users = User::factory(5)->create();

        $question = Question::create([
            'title' => '¿Cuál será el resultado?',
            'type' => 'predictive',
            'match_id' => $match->id,
            'group_id' => $group->id,
            'points' => 300,
            'available_until' => now()->addHours(24)
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Victoria Home']);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'Victoria Away']);

        // Cada usuario responde
        foreach ($users as $index => $user) {
            $optionId = $index < 3 ? $question->options[0]->id : $question->options[1]->id;

            Answer::create([
                'user_id' => $user->id,
                'question_id' => $question->id,
                'question_option_id' => $optionId,
                'is_correct' => false,
                'points_earned' => 0
            ]);
        }

        // Verificar resultados
        VerifyQuestionResultsJob::dispatch();

        // Los primeros 3 usuarios adivinaron "Victoria Home" (CORRECTA)
        $correctUsers = Answer::where('is_correct', true)->get();
        $this->assertEquals(3, $correctUsers->count());

        // Verificar que cada usuario correcto tiene 300 puntos
        foreach ($correctUsers as $answer) {
            $this->assertEquals(300, $answer->points_earned);
        }
    }
}
