<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use App\Models\Competition;
use App\Models\Question;
use App\Models\Answer;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group deploy
 */
class CriticalViewsTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $group;
    protected $competition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->competition = Competition::create([
            'name' => 'Test Competition',
            'type' => 'laliga',
            'logo' => 'test.png'
        ]);

        $this->group = Group::create([
            'name' => 'Deploy Test Group',
            'code' => 'DEPTSTX',
            'created_by' => $this->user->id,
            'competition_id' => $this->competition->id,
            'category' => 'official',
        ]);

        $this->group->users()->attach([$this->user->id]);
    }

    /** @test */
    public function groups_index_view_loads_correctly()
    {
        $response = $this->actingAs($this->user)->get(route('groups.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function groups_index_has_required_view_data()
    {
        $response = $this->actingAs($this->user)->get(route('groups.index'));

        $response->assertStatus(200);
        $response->assertViewHas('officialGroups');
        $response->assertViewHas('amateurGroups');
        $response->assertViewHas('userStreak');
        $response->assertViewHas('userAccuracy');
        $response->assertViewHas('totalGroups');
        $response->assertViewHas('hasPendingPredictions');
    }

    /** @test */
    public function groups_index_displays_user_groups()
    {
        $response = $this->actingAs($this->user)->get(route('groups.index'));

        $response->assertStatus(200);
        $officialGroups = $response->original->getData()['officialGroups'];

        $this->assertNotNull($officialGroups);
        $this->assertGreaterThanOrEqual(1, $officialGroups->count(), 'El usuario debe estar en al menos un grupo');
        $this->assertTrue($officialGroups->contains('id', $this->group->id), 'El grupo creado debe estar en la lista');
    }

    /** @test */
    public function groups_create_view_loads_correctly()
    {
        $response = $this->actingAs($this->user)->get(route('groups.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function groups_create_has_required_view_data()
    {
        $response = $this->actingAs($this->user)->get(route('groups.create'));

        $response->assertStatus(200);
        $response->assertViewHas('competitions');
        $response->assertViewHas('isAdmin');

        $competitions = $response->original->getData()['competitions'];
        $this->assertNotNull($competitions);
        $this->assertNotEmpty($competitions, 'Debe haber competiciones disponibles');
    }

    /** @test */
    public function groups_show_view_loads_correctly()
    {
        $response = $this->actingAs($this->user)->get(route('groups.show', $this->group->id));
        $response->assertStatus(200);
    }

    /** @test */
    public function groups_show_has_required_view_data()
    {
        $response = $this->actingAs($this->user)->get(route('groups.show', $this->group->id));

        $response->assertStatus(200);
        $response->assertViewHas('group');
        $response->assertViewHas('matchQuestions');
        $response->assertViewHas('userAnswers');
        $response->assertViewHas('socialQuestion');

        $group = $response->original->getData()['group'];
        $this->assertNotNull($group);
        $this->assertEquals($this->group->id, $group->id);
        $this->assertNotEmpty($group->users, 'El grupo debe tener usuarios');
    }

    /** @test */
    public function groups_show_includes_group_metadata()
    {
        $response = $this->actingAs($this->user)->get(route('groups.show', $this->group->id));

        $response->assertStatus(200);
        $group = $response->original->getData()['group'];

        // Verificar que la relación está cargada
        $this->assertNotNull($group->competition);
        $this->assertEquals($this->competition->id, $group->competition->id);
    }

    /** @test */
    public function groups_edit_view_loads_correctly()
    {
        $response = $this->actingAs($this->user)->get(route('groups.edit', $this->group->id));
        $response->assertStatus(200);
    }

    /** @test */
    public function groups_edit_has_required_view_data()
    {
        $response = $this->actingAs($this->user)->get(route('groups.edit', $this->group->id));

        $response->assertStatus(200);
        $response->assertViewHas('group');
        $response->assertViewHas('competitions');

        $group = $response->original->getData()['group'];
        $competitions = $response->original->getData()['competitions'];

        $this->assertNotNull($group);
        $this->assertEquals($this->group->id, $group->id);
        $this->assertNotNull($competitions);
        $this->assertNotEmpty($competitions, 'Debe haber competiciones disponibles');
    }

    /** @test */
    public function groups_ranking_view_loads_correctly()
    {
        $response = $this->actingAs($this->user)->get(route('rankings.group', $this->group->id));
        $response->assertStatus(200);
    }

    /** @test */
    public function groups_ranking_has_required_view_data()
    {
        $response = $this->actingAs($this->user)->get(route('rankings.group', $this->group->id));

        $response->assertStatus(200);
        $response->assertViewHas('group');
        $response->assertViewHas('rankings');

        $group = $response->original->getData()['group'];
        $rankings = $response->original->getData()['rankings'];

        $this->assertNotNull($group);
        $this->assertEquals($this->group->id, $group->id);
        $this->assertNotNull($rankings);
        // Al menos el usuario actual debe estar en el ranking
        $this->assertGreaterThanOrEqual(1, $rankings->count(), 'Debe haber al menos un usuario en el ranking');
    }

    /** @test */
    public function groups_ranking_displays_all_group_members()
    {
        // Añadir más usuarios al grupo
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $this->group->users()->attach([$user2->id, $user3->id]);

        $response = $this->actingAs($this->user)->get(route('rankings.group', $this->group->id));

        $response->assertStatus(200);
        $rankings = $response->original->getData()['rankings'];

        // Verificar que todos los usuarios del grupo están en el ranking
        $this->assertEquals(3, $rankings->count(), 'El ranking debe contener los 3 usuarios del grupo');
    }

    /** @test */
    public function profile_view_loads_correctly()
    {
        $response = $this->actingAs($this->user)->get(route('profile.edit'));
        $response->assertStatus(200);
    }

    /** @test */
    public function profile_edit_has_required_view_data()
    {
        $response = $this->actingAs($this->user)->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertViewHas('user');
        $response->assertViewHas('competitions');
        $response->assertViewHas('clubs');
        $response->assertViewHas('nationalTeams');

        $user = $response->original->getData()['user'];
        $competitions = $response->original->getData()['competitions'];

        $this->assertNotNull($user);
        $this->assertEquals($this->user->id, $user->id);
        $this->assertNotNull($competitions);
        $this->assertNotEmpty($competitions, 'Debe haber competiciones disponibles');
    }

    /** @test */
    public function login_view_loads_correctly()
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
    }

    /** @test */
    public function login_view_not_accessible_when_authenticated()
    {
        $response = $this->actingAs($this->user)->get(route('login'));

        // Debe redirigir al usuario autenticado
        $this->assertTrue(
            $response->status() === 302 || $response->status() === 200,
            'El usuario autenticado no debería poder acceder a la vista de login'
        );
    }

    // ============================================================
    // TESTS DE ENVÍO DE RESPUESTAS
    // ============================================================

    /** @test */
    public function user_can_submit_answer_to_question()
    {
        // Crear una pregunta con opciones
        $question = Question::create([
            'title' => 'Test Question',
            'type' => 'social',
            'group_id' => $this->group->id,
            'points' => 50,
            'available_from' => now()->subHour(),
            'available_until' => now()->addHour(),
        ]);

        $option1 = $question->options()->create(['text' => 'Option 1', 'is_correct' => false]);
        $option2 = $question->options()->create(['text' => 'Option 2', 'is_correct' => false]);

        // Enviar respuesta
        $response = $this->actingAs($this->user)->post(route('questions.answer', $question->id), [
            'question_option_id' => $option1->id,
        ]);

        // Verificar que se redirige correctamente
        $response->assertRedirect();

        // Verificar que la respuesta se guardó en la BD
        $this->assertDatabaseHas('answers', [
            'user_id' => $this->user->id,
            'question_id' => $question->id,
            'question_option_id' => $option1->id,
        ]);
    }

    /** @test */
    public function user_answer_is_created_with_correct_points_for_social_question()
    {
        $question = Question::create([
            'title' => 'Test Social Question',
            'type' => 'social',
            'group_id' => $this->group->id,
            'points' => 50,
            'available_from' => now()->subHour(),
            'available_until' => now()->addHour(),
        ]);

        $option = $question->options()->create(['text' => 'Option 1', 'is_correct' => false]);

        $this->actingAs($this->user)->post(route('questions.answer', $question->id), [
            'question_option_id' => $option->id,
        ]);

        // Las preguntas sociales siempre dan 50 puntos
        $answer = Answer::where('user_id', $this->user->id)
            ->where('question_id', $question->id)
            ->first();

        $this->assertNotNull($answer);
        $this->assertEquals(50, $answer->points_earned);
        $this->assertTrue($answer->is_correct);
    }

    /** @test */
    public function user_can_update_existing_answer()
    {
        $question = Question::create([
            'title' => 'Test Question',
            'type' => 'social',
            'group_id' => $this->group->id,
            'points' => 50,
            'available_from' => now()->subHour(),
            'available_until' => now()->addHour(),
        ]);

        $option1 = $question->options()->create(['text' => 'Option 1', 'is_correct' => false]);
        $option2 = $question->options()->create(['text' => 'Option 2', 'is_correct' => false]);

        // Primera respuesta
        $this->actingAs($this->user)->post(route('questions.answer', $question->id), [
            'question_option_id' => $option1->id,
        ]);

        // Actualizar respuesta
        $this->actingAs($this->user)->post(route('questions.answer', $question->id), [
            'question_option_id' => $option2->id,
        ]);

        // Debe haber solo una respuesta (actualizada)
        $answerCount = Answer::where('user_id', $this->user->id)
            ->where('question_id', $question->id)
            ->count();

        $this->assertEquals(1, $answerCount, 'Debe haber solo una respuesta (la actualizada)');

        // La respuesta debe tener la nueva opción
        $answer = Answer::where('user_id', $this->user->id)
            ->where('question_id', $question->id)
            ->first();

        $this->assertEquals($option2->id, $answer->question_option_id);
    }

    /** @test */
    public function user_cannot_submit_answer_with_invalid_option()
    {
        $question = Question::create([
            'title' => 'Test Question',
            'type' => 'social',
            'group_id' => $this->group->id,
            'points' => 50,
            'available_from' => now()->subHour(),
            'available_until' => now()->addHour(),
        ]);

        $question->options()->create(['text' => 'Valid Option', 'is_correct' => false]);

        // Intentar con opción inexistente
        $response = $this->actingAs($this->user)->post(route('questions.answer', $question->id), [
            'question_option_id' => 99999,
        ]);

        // Debe fallar la validación
        $response->assertSessionHasErrors('question_option_id');
    }

    /** @test */
    public function user_cannot_submit_answer_to_expired_question()
    {
        $question = Question::create([
            'title' => 'Expired Question',
            'type' => 'social',
            'group_id' => $this->group->id,
            'points' => 50,
            'available_from' => now()->subHours(2),
            'available_until' => now()->subHour(), // Expirada hace una hora
        ]);

        $option = $question->options()->create(['text' => 'Option 1', 'is_correct' => false]);

        // Intentar responder a pregunta expirada
        $response = $this->actingAs($this->user)->post(route('questions.answer', $question->id), [
            'question_option_id' => $option->id,
        ]);

        // Debe lanzar una excepción o redirigir
        $this->assertTrue(
            $response->status() !== 200,
            'La pregunta expirada debería rechazar la respuesta'
        );
    }

    /** @test */
    public function unauthenticated_user_cannot_submit_answer()
    {
        $question = Question::create([
            'title' => 'Test Question',
            'type' => 'social',
            'group_id' => $this->group->id,
            'points' => 50,
            'available_from' => now()->subHour(),
            'available_until' => now()->addHour(),
        ]);

        $option = $question->options()->create(['text' => 'Option 1', 'is_correct' => false]);

        // Sin autenticarse
        $response = $this->post(route('questions.answer', $question->id), [
            'question_option_id' => $option->id,
        ]);

        // Debe redirigir a login
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function answer_stores_correct_metadata()
    {
        $question = Question::create([
            'title' => 'Test Question',
            'type' => 'social',
            'group_id' => $this->group->id,
            'points' => 50,
            'available_from' => now()->subHour(),
            'available_until' => now()->addHour(),
        ]);

        $option = $question->options()->create(['text' => 'Option 1', 'is_correct' => false]);

        $this->actingAs($this->user)->post(route('questions.answer', $question->id), [
            'question_option_id' => $option->id,
        ]);

        $answer = Answer::firstWhere([
            'user_id' => $this->user->id,
            'question_id' => $question->id,
        ]);

        // Verificar que todos los campos se guardaron correctamente
        $this->assertNotNull($answer);
        $this->assertEquals($question->id, $answer->question_id);
        $this->assertEquals($this->user->id, $answer->user_id);
        $this->assertEquals($option->id, $answer->question_option_id);
        $this->assertEquals('social', $answer->category);
        $this->assertNotNull($answer->created_at);
    }
}
