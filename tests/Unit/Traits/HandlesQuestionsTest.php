<?php

namespace Tests\Unit\Traits;

use Tests\TestCase;
use App\Models\Group;
use App\Models\User;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\FootballMatch;
use App\Models\Competition;
use App\Traits\HandlesQuestions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TestClass
{
    use HandlesQuestions;

    public function testGetMatchQuestions($group, $roles)
    {
        return $this->getMatchQuestions($group, $roles);
    }

    public function testGetSocialQuestion($group, $roles)
    {
        return $this->getSocialQuestion($group, $roles);
    }

    public function testGetUserAnswers($group, $matchQuestions, $socialQuestion)
    {
        return $this->getUserAnswers($group, $matchQuestions, $socialQuestion);
    }

    public function testSetQuestionModificationStatus($question)
    {
        return $this->setQuestionModificationStatus($question);
    }

    // Stub para evitar error en el trait
    protected function createPredictiveQuestion($group)
    {
        // Devuelve una colección vacía para los tests
        return collect();
    }
}

class HandlesQuestionsTest extends TestCase
{
    use RefreshDatabase;

    protected $group;
    protected $users;
    protected $competition;
    protected $matches;
    protected $testClass;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear competencia
        $this->competition = Competition::create([
            'name' => 'La Liga',
            'type' => 'laliga'
        ]);

        // Crear usuarios
        $this->users = User::factory()->count(3)->create();

        // Crear grupo
        $this->group = Group::create([
            'name' => 'Grupo de Prueba',
            'code' => 'TEST123',
            'competition_id' => $this->competition->id,
            'created_by' => $this->users[0]->id
        ]);

        // Asignar usuarios al grupo
        foreach ($this->users as $user) {
            $this->group->users()->attach($user->id);
        }

        // Crear partidos
        $this->matches = collect([
            FootballMatch::create([
                'home_team' => 'Real Madrid',
                'away_team' => 'Barcelona',
                'date' => now()->addDay(),
                'status' => 'Not Started',
                'league' => 'laliga',
                'is_featured' => true
            ]),
            FootballMatch::create([
                'home_team' => 'Atlético Madrid',
                'away_team' => 'Sevilla',
                'date' => now()->addDay(),
                'status' => 'Not Started',
                'league' => 'laliga',
                'is_featured' => false
            ])
        ]);

        $this->testClass = new TestClass();
    }

    /** @test */
    public function it_can_get_match_questions()
    {
        // Crear preguntas de prueba
        $question = Question::create([
            'title' => '¿Quién ganará?',
            'type' => 'predictive',
            'group_id' => $this->group->id,
            'match_id' => $this->matches[0]->id,
            'available_until' => now()->addDay(),
            'points' => 10
        ]);

        QuestionOption::create([
            'question_id' => $question->id,
            'text' => 'Real Madrid',
            'is_correct' => false
        ]);

        $questions = $this->testClass->testGetMatchQuestions($this->group, collect());

        $this->assertCount(1, $questions);
        $this->assertTrue(Cache::has("group_{$this->group->id}_match_questions"));
    }

    /** @test */
    public function it_can_get_social_question()
    {
        // Crear pregunta social
        $question = Question::create([
            'title' => '¿Quién será el MVP?',
            'type' => 'social',
            'group_id' => $this->group->id,
            'available_until' => now()->addDay(),
            'points' => 10
        ]);

        foreach ($this->users as $user) {
            QuestionOption::create([
                'question_id' => $question->id,
                'text' => $user->name,
                'is_correct' => false
            ]);
        }

        $socialQuestion = $this->testClass->testGetSocialQuestion($this->group, collect());

        $this->assertNotNull($socialQuestion);
        $this->assertEquals('social', $socialQuestion->type);
        $this->assertCount(3, $socialQuestion->options);
        $this->assertTrue(Cache::has("group_{$this->group->id}_social_question"));
    }

    /** @test */
    public function it_can_get_user_answers()
    {
        // Crear pregunta y respuesta
        $question = Question::create([
            'title' => '¿Quién ganará?',
            'type' => 'predictive',
            'group_id' => $this->group->id,
            'match_id' => $this->matches[0]->id,
            'available_until' => now()->addDay(),
            'points' => 10
        ]);

        $option = QuestionOption::create([
            'question_id' => $question->id,
            'text' => 'Real Madrid',
            'is_correct' => false
        ]);

        // Asegurarse de que el usuario, pregunta y opción existen y están relacionados
        $this->users[0]->refresh();
        $question->refresh();
        $option->refresh();

        // Depuración temporal
        dump([
            'question_id' => $question->id,
            'option_id' => $option->id,
            'user_id' => $this->users[0]->id
        ]);

        // Crear la respuesta usando el modelo Answer directamente
        \App\Models\Answer::create([
            'question_id' => $question->id,
            'question_option_id' => $option->id,
            'user_id' => $this->users[0]->id,
            'points_earned' => 0
        ]);

        $answers = $this->testClass->testGetUserAnswers($this->group, collect([$question]), null);

        $this->assertCount(1, $answers);
        $this->assertTrue(Cache::has("user_{$this->group->id}_answers"));
    }

    /** @test */
    public function it_handles_question_modification_status()
    {
        // Crear pregunta con partido
        $question = Question::create([
            'title' => '¿Quién ganará?',
            'type' => 'predictive',
            'group_id' => $this->group->id,
            'match_id' => $this->matches[0]->id,
            'available_until' => now()->addDay(),
            'points' => 10
        ]);

        $option = QuestionOption::create([
            'question_id' => $question->id,
            'text' => 'Real Madrid',
            'is_correct' => false
        ]);

        // Asegurarse de que el usuario, pregunta y opción existen y están relacionados
        $this->users[0]->refresh();
        $question->refresh();
        $option->refresh();

        // Depuración temporal
        dump([
            'question_id' => $question->id,
            'option_id' => $option->id,
            'user_id' => $this->users[0]->id
        ]);

        // Crear la respuesta usando el modelo Answer directamente
        \App\Models\Answer::create([
            'question_id' => $question->id,
            'question_option_id' => $option->id,
            'user_id' => $this->users[0]->id,
            'points_earned' => 0,
            'created_at' => now()
        ]);

        $this->testClass->testSetQuestionModificationStatus($question);

        $this->assertTrue($question->can_modify);
    }
}
