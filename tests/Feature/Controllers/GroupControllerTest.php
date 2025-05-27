<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\Group;
use App\Models\User;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\FootballMatch;
use App\Models\Competition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class GroupControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $group;
    protected $competition;
    protected $otherUser;
    protected $match;
    protected $predictiveTemplate;
    protected $socialTemplate;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear competencia
        $this->competition = Competition::create([
            'name' => 'La Liga',
            'type' => 'laliga',
            'logo' => 'test.png'
        ]);

        // Crear usuarios
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        // Crear grupo
        $this->group = Group::create([
            'name' => 'Test Group',
            'code' => 'TEST123',
            'created_by' => $this->user->id,
            'competition_id' => $this->competition->id
        ]);

        // Agregar usuarios al grupo
        $this->group->users()->attach([$this->user->id, $this->otherUser->id]);

        // Crear partidos
        $this->match = FootballMatch::create([
            'home_team' => 'Real Madrid',
            'away_team' => 'Barcelona',
            'date' => now()->addDay(),
            'league' => 'laliga',
            'is_featured' => true
        ]);

        // Crear plantillas de preguntas
        $this->predictiveTemplate = \App\Models\TemplateQuestion::create([
            'text' => '¿Quién ganará el partido {{home_team}} vs {{away_team}}?',
            'type' => 'predictive',
            'competition_id' => $this->competition->id,
            'options' => [
                ['text' => '{{home_team}}', 'is_correct' => false],
                ['text' => 'Empate', 'is_correct' => false],
                ['text' => '{{away_team}}', 'is_correct' => false]
            ],
            'points' => 300
        ]);

        $this->socialTemplate = \App\Models\TemplateQuestion::create([
            'text' => '¿Quién será el MVP del grupo hoy?',
            'type' => 'social',
            'options' => [],
            'points' => 100
        ]);
    }

    /** @test */
    public function it_can_show_group_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('groups.show', $this->group));

        $response->assertStatus(200)
            ->assertViewIs('groups.show')
            ->assertViewHas('group')
            ->assertViewHas('matchQuestions')
            ->assertViewHas('userAnswers')
            ->assertViewHas('socialQuestion');
    }

    /** @test */
    public function it_creates_predictive_questions_when_needed()
    {
        $response = $this->actingAs($this->user)
            ->get(route('groups.show', $this->group));

        $response->assertStatus(200);

        // Verificar que se crearon preguntas predictivas
        $this->assertDatabaseHas('questions', [
            'group_id' => $this->group->id,
            'type' => 'predictive',
            'points' => 300
        ]);
    }

    /** @test */
    public function it_creates_social_question_when_needed()
    {
        $response = $this->actingAs($this->user)
            ->get(route('groups.show', $this->group));

        $response->assertStatus(200);

        // Verificar que se creó la pregunta social
        $this->assertDatabaseHas('questions', [
            'group_id' => $this->group->id,
            'type' => 'social',
            'points' => 100
        ]);

        // Verificar que se crearon las opciones para cada usuario
        $socialQuestion = Question::where('type', 'social')
            ->where('group_id', $this->group->id)
            ->first();

        $this->assertNotNull($socialQuestion);
        $this->assertEquals(2, $socialQuestion->options()->count());
    }

    /** @test */
    public function it_handles_question_modification_window()
    {
        // Crear pregunta y respuesta
        $question = Question::create([
            'title' => '¿Quién ganará?',
            'type' => 'predictive',
            'group_id' => $this->group->id,
            'available_until' => now()->addDay(),
            'points' => 10
        ]);

        $option = QuestionOption::create([
            'question_id' => $question->id,
            'text' => 'Real Madrid',
            'is_correct' => false
        ]);

        // Asegurarse de que el usuario, pregunta y opción existen y están relacionados
        $this->user->refresh();
        $question->refresh();
        $option->refresh();

        // Crear la respuesta usando el modelo Answer directamente
        \App\Models\Answer::create([
            'question_id' => $question->id,
            'question_option_id' => $option->id,
            'user_id' => $this->user->id,
            'points_earned' => 0,
            'created_at' => now()
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('groups.show', $this->group));

        $response->assertStatus(200)
            ->assertViewHas('matchQuestions', function ($questions) {
                return $questions->first()->can_modify === true;
            });
    }

    /** @test */
    public function it_prevents_access_to_unauthorized_users()
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->get(route('groups.show', $this->group));

        $response->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'No tienes acceso a este grupo.');
    }
}
