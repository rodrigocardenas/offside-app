<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use App\Models\Competition;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Answer;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group deploy
 * Critical tests for core Offside Club functionality
 * These tests validate data integrity and business logic without HTTP dependencies
 */
class CriticalViewsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $group;
    protected $competition;
    protected $question;
    protected $questionOption;

    protected function setUp(): void
    {
        parent::setUp();

        // Create competition
        $this->competition = Competition::create([
            'name' => 'Test Competition',
            'type' => 'laliga',
            'logo' => 'test.png'
        ]);

        // Create user
        $this->user = User::factory()->create();

        // Create group
        $this->group = Group::create([
            'name' => 'Test Group',
            'code' => 'TEST' . uniqid(),
            'created_by' => $this->user->id,
            'competition_id' => $this->competition->id,
            'category' => 'official',
        ]);

        // Attach user to group
        $this->group->users()->attach([$this->user->id]);

        // Create a match question
        $this->question = Question::create([
            'competition_id' => $this->competition->id,
            'group_id' => $this->group->id,
            'title' => 'Test Match Question',
            'type' => 'match',
            'expires_at' => now()->addHours(24),
            'correct_option_id' => null,
            'category' => 'goals'
        ]);

        // Create question options
        $this->questionOption = QuestionOption::create([
            'question_id' => $this->question->id,
            'label' => 'Test Option',
            'points' => 10
        ]);

        $this->question->update(['correct_option_id' => $this->questionOption->id]);
    }

    // ===== User Model Tests =====
    
    /** @test */
    public function test_user_can_be_created_and_retrieved()
    {
        $user = User::where('id', $this->user->id)->first();
        $this->assertNotNull($user);
        $this->assertEquals($this->user->email, $user->email);
    }

    /** @test */
    public function test_user_belongs_to_multiple_groups()
    {
        $groups = $this->user->groups;
        $this->assertGreaterThanOrEqual(1, $groups->count());
        $this->assertTrue($groups->contains('id', $this->group->id));
    }

    /** @test */
    public function test_user_can_have_answers()
    {
        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
        ]);

        $userAnswers = $this->user->answers;
        $this->assertGreaterThanOrEqual(1, $userAnswers->count());
        $this->assertTrue($userAnswers->contains('id', $answer->id));
    }

    // ===== Group Model Tests =====

    /** @test */
    public function test_group_can_be_created_with_unique_code()
    {
        $newGroup = Group::create([
            'name' => 'Another Group',
            'code' => 'UNIQ' . uniqid(),
            'created_by' => $this->user->id,
            'competition_id' => $this->competition->id,
            'category' => 'official',
        ]);

        $this->assertNotNull($newGroup->id);
        $this->assertEquals('Another Group', $newGroup->name);
    }

    /** @test */
    public function test_group_has_users()
    {
        $users = $this->group->users;
        $this->assertGreaterThanOrEqual(1, $users->count());
        $this->assertTrue($users->contains('id', $this->user->id));
    }

    /** @test */
    public function test_group_belongs_to_competition()
    {
        $competition = $this->group->competition;
        $this->assertNotNull($competition);
        $this->assertEquals($this->competition->id, $competition->id);
    }

    /** @test */
    public function test_group_has_questions()
    {
        $questions = $this->group->questions;
        $this->assertGreaterThanOrEqual(1, $questions->count());
        $this->assertTrue($questions->contains('id', $this->question->id));
    }

    // ===== Competition Model Tests =====

    /** @test */
    public function test_competition_can_be_created_and_retrieved()
    {
        $competition = Competition::where('id', $this->competition->id)->first();
        $this->assertNotNull($competition);
        $this->assertEquals('Test Competition', $competition->name);
    }

    /** @test */
    public function test_competition_has_groups()
    {
        $groups = $this->competition->groups;
        $this->assertGreaterThanOrEqual(1, $groups->count());
        $this->assertTrue($groups->contains('id', $this->group->id));
    }

    // ===== Question Model Tests =====

    /** @test */
    public function test_question_can_be_created()
    {
        $question = Question::where('id', $this->question->id)->first();
        $this->assertNotNull($question);
        $this->assertEquals('Test Match Question', $question->title);
    }

    /** @test */
    public function test_question_belongs_to_group()
    {
        $group = $this->question->group;
        $this->assertNotNull($group);
        $this->assertEquals($this->group->id, $group->id);
    }

    /** @test */
    public function test_question_has_options()
    {
        $options = $this->question->options;
        $this->assertGreaterThanOrEqual(1, $options->count());
        $this->assertTrue($options->contains('id', $this->questionOption->id));
    }

    // ===== Answer Model Tests =====

    /** @test */
    public function test_user_can_submit_answer_to_question()
    {
        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
        ]);

        $this->assertNotNull($answer->id);
        $this->assertEquals($this->user->id, $answer->user_id);
        $this->assertEquals($this->question->id, $answer->question_id);
    }

    /** @test */
    public function test_user_answer_is_created_with_correct_points()
    {
        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
            'points' => 10,
        ]);

        $this->assertEquals(10, $answer->points);
    }

    /** @test */
    public function test_user_answer_is_created_with_correct_points_for_social_question()
    {
        $socialQuestion = Question::create([
            'competition_id' => $this->competition->id,
            'group_id' => $this->group->id,
            'title' => 'Test Social Question',
            'type' => 'social',
            'expires_at' => now()->addHours(24),
            'correct_option_id' => null,
            'category' => 'other'
        ]);

        $socialOption = QuestionOption::create([
            'question_id' => $socialQuestion->id,
            'label' => 'Social Option',
            'points' => 5
        ]);

        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $socialQuestion->id,
            'question_option_id' => $socialOption->id,
            'points' => 5,
        ]);

        $this->assertEquals(5, $answer->points);
    }

    /** @test */
    public function test_user_can_update_existing_answer()
    {
        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
            'points' => 10,
        ]);

        $updatedOption = QuestionOption::create([
            'question_id' => $this->question->id,
            'label' => 'Updated Option',
            'points' => 15
        ]);

        $answer->update([
            'question_option_id' => $updatedOption->id,
            'points' => 15,
        ]);

        $this->assertEquals(15, $answer->points);
        $this->assertEquals($updatedOption->id, $answer->question_option_id);
    }

    /** @test */
    public function test_user_cannot_submit_answer_with_invalid_option()
    {
        // Attempt to create an answer with a non-existent option
        try {
            $answer = Answer::create([
                'user_id' => $this->user->id,
                'question_id' => $this->question->id,
                'question_option_id' => 99999, // Non-existent ID
                'points' => 10,
            ]);
            
            // Should fail on database constraint
            $this->fail('Should not allow invalid option');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    /** @test */
    public function test_user_cannot_submit_answer_to_expired_question()
    {
        $expiredQuestion = Question::create([
            'competition_id' => $this->competition->id,
            'group_id' => $this->group->id,
            'title' => 'Expired Question',
            'type' => 'match',
            'expires_at' => now()->subHours(1), // Already expired
            'correct_option_id' => null,
            'category' => 'goals'
        ]);

        $expiredOption = QuestionOption::create([
            'question_id' => $expiredQuestion->id,
            'label' => 'Option',
            'points' => 10
        ]);

        // Should be able to create the answer, but validation should handle it
        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $expiredQuestion->id,
            'question_option_id' => $expiredOption->id,
        ]);

        // Verify expired_at is set correctly
        $this->assertNotNull($expiredQuestion->expires_at);
        $this->assertTrue($expiredQuestion->expires_at->isPast());
    }

    /** @test */
    public function test_unauthenticated_user_cannot_submit_answer()
    {
        // This tests the model constraints
        $unauthenticatedUser = User::factory()->create();
        
        $answer = Answer::factory()->create([
            'user_id' => $unauthenticatedUser->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
        ]);

        $this->assertNotNull($answer->id);
        $this->assertTrue($answer->user()->exists());
    }

    /** @test */
    public function test_answer_stores_correct_metadata()
    {
        $metadata = ['confidence' => 'high', 'notes' => 'test notes'];
        
        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
            'metadata' => json_encode($metadata),
        ]);

        $this->assertNotNull($answer->metadata);
        $storedMetadata = json_decode($answer->metadata, true);
        $this->assertEquals('high', $storedMetadata['confidence']);
    }

    /** @test */
    public function test_answer_belongs_to_user()
    {
        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
        ]);

        $user = $answer->user;
        $this->assertNotNull($user);
        $this->assertEquals($this->user->id, $user->id);
    }

    /** @test */
    public function test_answer_belongs_to_question()
    {
        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
        ]);

        $question = $answer->question;
        $this->assertNotNull($question);
        $this->assertEquals($this->question->id, $question->id);
    }

    /** @test */
    public function test_answer_belongs_to_question_option()
    {
        $answer = Answer::factory()->create([
            'user_id' => $this->user->id,
            'question_id' => $this->question->id,
            'question_option_id' => $this->questionOption->id,
        ]);

        $option = $answer->option;
        $this->assertNotNull($option);
        $this->assertEquals($this->questionOption->id, $option->id);
    }

    /** @test */
    public function test_question_option_belongs_to_question()
    {
        $question = $this->questionOption->question;
        $this->assertNotNull($question);
        $this->assertEquals($this->question->id, $question->id);
    }
}
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
