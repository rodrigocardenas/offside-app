<?php

namespace Tests\Feature\Jobs;

use Tests\TestCase;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Answer;
use App\Models\FootballMatch;
use App\Models\User;
use App\Models\Group;
use App\Jobs\VerifyQuestionResultsJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VerifyQuestionResultsJobTest extends TestCase
{
    use RefreshDatabase;

    private FootballMatch $match;
    private Group $group;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario y grupo
        $this->user = User::factory()->create();
        $this->group = Group::factory()->create();

        // Crear partido terminado
        $this->match = FootballMatch::create([
            'external_id' => 5555,
            'home_team' => 'Arsenal',
            'away_team' => 'Liverpool',
            'date' => now()->subHours(2),
            'status' => 'FINISHED',
            'home_team_score' => 2,
            'away_team_score' => 1,
            'league' => 'PL',
            'events' => json_encode([
                ['type' => 'GOAL', 'team' => 'HOME', 'minute' => 15],
                ['type' => 'GOAL', 'team' => 'AWAY', 'minute' => 35],
                ['type' => 'GOAL', 'team' => 'HOME', 'minute' => 70]
            ])
        ]);
    }

    /**
     * Test que el Job verifica preguntas de partidos finalizados
     */
    public function test_job_verifies_finished_match_questions(): void
    {
        // Crear pregunta sin verificar
        $question = Question::create([
            'title' => '¿Cuál será el resultado?',
            'type' => 'predictive',
            'match_id' => $this->match->id,
            'group_id' => $this->group->id,
            'points' => 300,
            'available_until' => now()->addHours(24),
            'result_verified_at' => null
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Victoria Arsenal', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'Victoria Liverpool', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'Empate', 'is_correct' => false]);

        // Crear respuesta de usuario (opción equivocada)
        $answer = Answer::create([
            'user_id' => $this->user->id,
            'question_id' => $question->id,
            'question_option_id' => $question->options[1]->id, // Liverpool (incorrecto)
            'is_correct' => false,
            'points_earned' => 0
        ]);

        // Ejecutar Job
        VerifyQuestionResultsJob::dispatch();

        // Verificar que la pregunta fue marcada como verificada
        $this->assertNotNull($question->refresh()->result_verified_at);

        // Verificar que se actualizó la opción correcta
        $this->assertTrue($question->options[0]->refresh()->is_correct);
        $this->assertFalse($question->options[1]->refresh()->is_correct);

        // Verificar que la respuesta del usuario se marcó incorrecta
        $this->assertFalse($answer->refresh()->is_correct);
        $this->assertEquals(0, $answer->points_earned);
    }

    /**
     * Test que el Job asigna puntos correctamente
     */
    public function test_job_assigns_correct_points(): void
    {
        $question = Question::create([
            'title' => '¿Quién anotará el primer gol?',
            'type' => 'predictive',
            'match_id' => $this->match->id,
            'group_id' => $this->group->id,
            'points' => 300,
            'available_until' => now()->addHours(24),
            'result_verified_at' => null
        ]);

        QuestionOption::create(['question_id' => $question->id, 'text' => 'Arsenal', 'is_correct' => false]);
        QuestionOption::create(['question_id' => $question->id, 'text' => 'Liverpool', 'is_correct' => false]);

        // Usuario responde correctamente (Arsenal anotó primer gol)
        $answer = Answer::create([
            'user_id' => $this->user->id,
            'question_id' => $question->id,
            'question_option_id' => $question->options[0]->id,
            'is_correct' => false,
            'points_earned' => 0
        ]);

        // Ejecutar Job
        VerifyQuestionResultsJob::dispatch();

        // Verificar que ganó puntos
        $this->assertTrue($answer->refresh()->is_correct);
        $this->assertEquals(300, $answer->points_earned);
    }

    /**
     * Test que el Job procesa múltiples preguntas
     */
    public function test_job_processes_multiple_questions(): void
    {
        // Crear 3 preguntas
        for ($i = 0; $i < 3; $i++) {
            Question::create([
                'title' => 'Pregunta de prueba ' . $i,
                'type' => 'predictive',
                'match_id' => $this->match->id,
                'group_id' => $this->group->id,
                'points' => 300,                'available_until' => now()->addHours(24),                'result_verified_at' => null
            ]);
        }

        // Ejecutar Job
        VerifyQuestionResultsJob::dispatch();

        // Verificar que todas fueron marcadas como verificadas
        $this->assertEquals(3, Question::whereNotNull('result_verified_at')->count());
    }

    /**
     * Test que el Job ignora preguntas ya verificadas
     */
    public function test_job_skips_already_verified_questions(): void
    {
        // Pregunta ya verificada
        $verified = Question::create([
            'title' => 'Pregunta verificada',
            'type' => 'predictive',
            'match_id' => $this->match->id,
            'group_id' => $this->group->id,
            'points' => 300,
            'available_until' => now()->addHours(24),
            'result_verified_at' => now()->subDay()
        ]);

        // Pregunta sin verificar
        $unverified = Question::create([
            'title' => 'Pregunta sin verificar',
            'type' => 'predictive',
            'match_id' => $this->match->id,
            'group_id' => $this->group->id,
            'points' => 300,
            'available_until' => now()->addHours(24),
            'result_verified_at' => null
        ]);

        QuestionOption::create(['question_id' => $unverified->id, 'text' => 'Opción', 'is_correct' => false]);

        // Ejecutar Job
        VerifyQuestionResultsJob::dispatch();

        // Solo la sin verificar debe ser procesada
        $this->assertEquals(1, Question::whereNotNull('result_verified_at')->where('result_verified_at', '!=', now()->subDay())->count());
    }

    /**
     * Test que el Job ignora partidos no finalizados
     */
    public function test_job_skips_unfinished_matches(): void
    {
        // Crear partido aún en juego
        $unfinishedMatch = FootballMatch::create([
            'external_id' => 6666,
            'home_team' => 'Chelsea',
            'away_team' => 'Manchester City',
            'date' => now(),
            'status' => 'LIVE',
            'home_team_score' => null,
            'away_team_score' => null,
            'league' => 'PL'
        ]);

        $question = Question::create([
            'title' => 'Pregunta de partido vivo',
            'type' => 'predictive',
            'match_id' => $unfinishedMatch->id,
            'group_id' => $this->group->id,
            'points' => 300,            'available_until' => now()->addHours(24),            'result_verified_at' => null
        ]);

        // Ejecutar Job
        VerifyQuestionResultsJob::dispatch();

        // Pregunta no debe ser verificada
        $this->assertNull($question->refresh()->result_verified_at);
    }

    /**
     * Test que el Job maneja errores gracefully
     */
    public function test_job_handles_errors_gracefully(): void
    {
        // Crear pregunta con opciones malformadas
        $question = Question::create([
            'title' => '¿Resultado?',
            'type' => 'predictive',
            'match_id' => $this->match->id,
            'group_id' => $this->group->id,
            'points' => 300,
            'available_until' => now()->addHours(24),
            'result_verified_at' => null
        ]);

        // Sin opciones (causará error)
        // El Job debe continuar sin fallar

        // Ejecutar Job
        VerifyQuestionResultsJob::dispatch();

        // No debe lanzar excepción, el Job debe continuar
        $this->assertTrue(true);
    }
}
