<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use App\Models\Group;
use App\Models\Competition;
use App\Models\Answer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Exceptions\QuestionException;
use Carbon\Carbon;

class QuestionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $group;
    protected $competition;
    protected $question;
    protected $option;

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

        // Crear grupo
        $this->group = Group::create([
            'name' => 'Test Group',
            'code' => 'TEST123',
            'created_by' => $this->user->id,
            'competition_id' => $this->competition->id
        ]);

        // Agregar usuario al grupo
        $this->group->users()->attach($this->user->id);

        // Crear pregunta
        $this->question = Question::create([
            'title' => '¿Quién ganará?',
            'type' => 'predictive',
            'group_id' => $this->group->id,
            'available_until' => now()->addDay(),
            'points' => 100
        ]);

        // Crear opción
        $this->option = QuestionOption::create([
            'question_id' => $this->question->id,
            'text' => 'Real Madrid',
            'is_correct' => true
        ]);
    }

    /** @test */
    public function it_can_show_question_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('questions.show', $this->question));

        $response->assertStatus(200)
            ->assertViewIs('questions.show')
            ->assertViewHas('question')
            ->assertViewHas('userAnswer');
    }

    /** @test */
    public function it_can_answer_question()
    {
        $response = $this->actingAs($this->user)
            ->post(route('questions.answer', $this->question), [
                'question_option_id' => $this->option->id
            ]);

        $response->assertRedirect(route('groups.show', $this->group));

        $this->assertDatabaseHas('answers', [
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->option->id
        ]);
    }

    /** @test */
    public function it_throws_exception_when_answering_expired_question()
    {
        // Hacer que la pregunta expire
        $this->question->update(['available_until' => now()->subDays(2)]);

        $this->expectException(QuestionException::class);
        $this->expectExceptionMessage('No puedes responder a esta pregunta en este momento.');

        $this->actingAs($this->user)
            ->post(route('questions.answer', $this->question), [
                'question_option_id' => $this->option->id
            ]);
    }

    /** @test */
    public function it_can_show_question_results()
    {
        // Marcar la pregunta como expirada
        $this->question->update(['available_until' => now()->subDay()]);

        $response = $this->actingAs($this->user)
            ->get(route('questions.results', $this->question));

        $response->assertStatus(200)
            ->assertViewIs('questions.results')
            ->assertViewHas('question')
            ->assertViewHas('answers');
    }

    /** @test */
    public function it_throws_exception_when_showing_results_for_active_question()
    {
        $this->expectException(QuestionException::class);
        $this->expectExceptionMessage('Los resultados aún no están disponibles.');

        $this->actingAs($this->user)
            ->get(route('questions.results', $this->question));
    }

    /** @test */
    public function it_can_react_to_template_question()
    {
        $templateQuestion = \App\Models\TemplateQuestion::create([
            'text' => '¿Pregunta de prueba?',
            'type' => 'social'
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('questions.react', $templateQuestion), [
                'reaction' => 'like'
            ]);

        $response->assertJson([
            'success' => true,
            'user_reaction' => 'like'
        ]);
    }

    /** @test */
    public function it_can_toggle_reaction_on_template_question()
    {
        $templateQuestion = \App\Models\TemplateQuestion::create([
            'text' => '¿Pregunta de prueba?',
            'type' => 'social'
        ]);

        // Primera reacción
        $this->actingAs($this->user)
            ->post(route('questions.react', $templateQuestion), [
                'reaction' => 'like'
            ]);

        // Misma reacción debería eliminarla
        $response = $this->actingAs($this->user)
            ->post(route('questions.react', $templateQuestion), [
                'reaction' => 'like'
            ]);

        $response->assertJson([
            'success' => true,
            'user_reaction' => null
        ]);
    }

    /** @test */
    public function it_validates_question_option_exists()
    {
        $response = $this->actingAs($this->user)
            ->post(route('questions.answer', $this->question), [
                'question_option_id' => 99999 // ID que no existe
            ]);

        $response->assertSessionHasErrors('question_option_id');
    }
}
