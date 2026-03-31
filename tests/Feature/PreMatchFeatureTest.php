<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Group;
use App\Models\PreMatch;
use App\Models\PreMatchProposition;
use App\Models\PreMatchVote;
use App\Models\PreMatchResolution;
use App\Models\GroupPenalty;
use App\Models\ActionTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreMatchFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $member1;
    protected User $member2;
    protected Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->admin = User::factory()->create();
        $this->member1 = User::factory()->create();
        $this->member2 = User::factory()->create();

        // Create group with admin
        $this->group = Group::factory()->create(['created_by' => $this->admin->id]);
        $this->group->users()->attach([$this->admin->id, $this->member1->id, $this->member2->id]);

        // Seed action templates
        ActionTemplate::factory()->count(20)->create();
    }

    /** @test */
    public function admin_can_create_pre_match_with_points_penalty()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/pre-matches', [
            'group_id' => $this->group->id,
            'penalty' => '-1000 puntos',
            'penalty_type' => 'POINTS',
            'penalty_points' => 1000,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('pre_matches', [
            'group_id' => $this->group->id,
            'penalty_type' => 'POINTS',
            'penalty_points' => 1000,
            'status' => 'OPEN',
        ]);
    }

    /** @test */
    public function admin_can_create_pre_match_with_social_penalty()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/pre-matches', [
            'group_id' => $this->group->id,
            'penalty' => 'Paga cena para 6',
            'penalty_type' => 'SOCIAL',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('pre_matches', [
            'penalty_type' => 'SOCIAL',
        ]);
    }

    /** @test */
    public function non_admin_cannot_create_pre_match()
    {
        $response = $this->actingAs($this->member1)->postJson('/api/pre-matches', [
            'group_id' => $this->group->id,
            'penalty' => '-1000 puntos',
            'penalty_type' => 'POINTS',
            'penalty_points' => 1000,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function member_can_add_proposition()
    {
        $preMatch = PreMatch::factory()->create([
            'group_id' => $this->group->id,
            'status' => 'OPEN',
        ]);

        $response = $this->actingAs($this->member1)->postJson(
            "/api/pre-matches/{$preMatch->id}/propositions",
            [
                'action' => '3+ goles de cabeza',
                'description' => 'Es posible pero raro',
            ]
        );

        $response->assertStatus(201);
        $this->assertDatabaseHas('pre_match_propositions', [
            'pre_match_id' => $preMatch->id,
            'user_id' => $this->member1->id,
            'action' => '3+ goles de cabeza',
            'validation_status' => 'PENDING',
        ]);
    }

    /** @test */
    public function member_can_vote_on_proposition()
    {
        $preMatch = PreMatch::factory()->create([
            'group_id' => $this->group->id,
            'status' => 'OPEN',
        ]);

        $prop = PreMatchProposition::factory()->create([
            'pre_match_id' => $preMatch->id,
            'user_id' => $this->member1->id,
            'validation_status' => 'PENDING',
        ]);

        $response = $this->actingAs($this->member2)->postJson(
            "/api/pre-match-propositions/{$prop->id}/vote",
            ['approved' => true]
        );

        $response->assertStatus(201);
        $this->assertDatabaseHas('pre_match_votes', [
            'proposition_id' => $prop->id,
            'user_id' => $this->member2->id,
            'approved' => true,
        ]);
    }

    /** @test */
    public function member_cannot_vote_own_proposition()
    {
        $preMatch = PreMatch::factory()->create([
            'group_id' => $this->group->id,
            'status' => 'OPEN',
        ]);

        $prop = PreMatchProposition::factory()->create([
            'pre_match_id' => $preMatch->id,
            'user_id' => $this->member1->id,
            'validation_status' => 'PENDING',
        ]);

        $response = $this->actingAs($this->member1)->postJson(
            "/api/pre-match-propositions/{$prop->id}/vote",
            ['approved' => true]
        );

        $response->assertStatus(403);
    }

    /** @test */
    public function proposition_accepted_with_over_50_percent_votes()
    {
        $preMatch = PreMatch::factory()->create([
            'group_id' => $this->group->id,
            'status' => 'OPEN',
        ]);

        $prop = PreMatchProposition::factory()->create([
            'pre_match_id' => $preMatch->id,
            'user_id' => $this->member1->id,
            'validation_status' => 'PENDING',
        ]);

        // 3 affirmative votes
        $voters = User::factory(3)->create();
        foreach ($voters as $voter) {
            PreMatchVote::create([
                'proposition_id' => $prop->id,
                'user_id' => $voter->id,
                'approved' => true,
            ]);
        }

        $prop->refresh();
        $this->assertEquals('ACCEPTED', $prop->validation_status);
        $this->assertGreaterThan(50, $prop->approval_percentage);
    }

    /** @test */
    public function proposition_rejected_with_under_50_percent_votes()
    {
        $preMatch = PreMatch::factory()->create([
            'group_id' => $this->group->id,
            'status' => 'OPEN',
        ]);

        $prop = PreMatchProposition::factory()->create([
            'pre_match_id' => $preMatch->id,
            'user_id' => $this->member1->id,
            'validation_status' => 'PENDING',
        ]);

        // 1 affirmative, 2 negative
        PreMatchVote::create([
            'proposition_id' => $prop->id,
            'user_id' => $this->member2->id,
            'approved' => true,
        ]);

        $voters = User::factory(2)->create();
        foreach ($voters as $voter) {
            PreMatchVote::create([
                'proposition_id' => $prop->id,
                'user_id' => $voter->id,
                'approved' => false,
            ]);
        }

        $prop->refresh();
        $this->assertEquals('REJECTED', $prop->validation_status);
        $this->assertLessThanOrEqual(50, $prop->approval_percentage);
    }

    /** @test */
    public function admin_can_resolve_pre_match_with_fulfilled_proposition()
    {
        $preMatch = PreMatch::factory()->create([
            'group_id' => $this->group->id,
            'created_by' => $this->admin->id,
            'penalty_type' => 'POINTS',
            'penalty_points' => 1000,
            'status' => 'LOCKED',
        ]);

        $prop = PreMatchProposition::factory()->create([
            'pre_match_id' => $preMatch->id,
            'user_id' => $this->member1->id,
            'validation_status' => 'ACCEPTED',
        ]);

        $this->member1->update(['tournament_points' => 5000]);

        $response = $this->actingAs($this->admin)->postJson(
            "/api/pre-matches/{$preMatch->id}/resolve",
            [
                'resolutions' => [
                    [
                        'proposition_id' => $prop->id,
                        'was_fulfilled' => true,
                        'admin_notes' => 'Confirmado en estadísticas',
                        'penalty_type' => 'POINTS',
                        'points_lost' => 1000,
                    ],
                ],
            ]
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('pre_match_resolutions', [
            'pre_match_id' => $preMatch->id,
            'proposition_id' => $prop->id,
            'was_fulfilled' => true,
        ]);

        // Verify points were deducted
        $this->assertEquals(4000, $this->member1->refresh()->tournament_points);
    }

    /** @test */
    public function admin_can_apply_all_points_penalty()
    {
        $preMatch = PreMatch::factory()->create([
            'group_id' => $this->group->id,
            'created_by' => $this->admin->id,
            'penalty_type' => 'POINTS',
            'status' => 'LOCKED',
        ]);

        $prop = PreMatchProposition::factory()->create([
            'pre_match_id' => $preMatch->id,
            'user_id' => $this->member1->id,
            'validation_status' => 'ACCEPTED',
        ]);

        $this->member1->update(['tournament_points' => 3500]);

        $this->actingAs($this->admin)->postJson(
            "/api/pre-matches/{$preMatch->id}/resolve",
            [
                'resolutions' => [
                    [
                        'proposition_id' => $prop->id,
                        'was_fulfilled' => true,
                        'penalty_type' => 'POINTS',
                        'points_lost' => 'ALL',
                    ],
                ],
            ]
        );

        $this->assertEquals(0, $this->member1->refresh()->tournament_points);
    }

    /** @test */
    public function admin_can_apply_social_penalty()
    {
        $preMatch = PreMatch::factory()->create([
            'group_id' => $this->group->id,
            'created_by' => $this->admin->id,
            'penalty_type' => 'SOCIAL',
            'status' => 'LOCKED',
        ]);

        $prop = PreMatchProposition::factory()->create([
            'pre_match_id' => $preMatch->id,
            'user_id' => $this->member1->id,
            'validation_status' => 'ACCEPTED',
        ]);

        $response = $this->actingAs($this->admin)->postJson(
            "/api/pre-matches/{$preMatch->id}/resolve",
            [
                'resolutions' => [
                    [
                        'proposition_id' => $prop->id,
                        'was_fulfilled' => true,
                        'penalty_type' => 'SOCIAL',
                        'penalty_description' => 'Paga cena para 6 en pizzería La Nonna',
                    ],
                ],
            ]
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('group_penalties', [
            'group_id' => $this->group->id,
            'user_id' => $this->member1->id,
            'penalty_type' => 'SOCIAL',
            'penalty_description' => 'Paga cena para 6 en pizzería La Nonna',
        ]);
    }

    /** @test */
    public function non_fulfilled_proposition_does_not_create_penalty()
    {
        $preMatch = PreMatch::factory()->create([
            'group_id' => $this->group->id,
            'created_by' => $this->admin->id,
            'penalty_type' => 'POINTS',
            'penalty_points' => 1000,
            'status' => 'LOCKED',
        ]);

        $prop = PreMatchProposition::factory()->create([
            'pre_match_id' => $preMatch->id,
            'user_id' => $this->member1->id,
            'validation_status' => 'ACCEPTED',
        ]);

        $originalPoints = 5000;
        $this->member1->update(['tournament_points' => $originalPoints]);

        $this->actingAs($this->admin)->postJson(
            "/api/pre-matches/{$preMatch->id}/resolve",
            [
                'resolutions' => [
                    [
                        'proposition_id' => $prop->id,
                        'was_fulfilled' => false,
                        'penalty_type' => 'POINTS',
                        'points_lost' => 1000,
                    ],
                ],
            ]
        );

        $this->assertEquals(0, GroupPenalty::count());
        $this->assertEquals($originalPoints, $this->member1->refresh()->tournament_points);
    }

    /** @test */
    public function get_random_action_template()
    {
        $response = $this->getJson('/api/action-templates/random');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'action',
            'probability',
            'category',
            'description',
        ]);
    }

    /** @test */
    public function get_action_template_by_probability()
    {
        $response = $this->getJson('/api/action-templates/random?probability=LOW');

        $response->assertStatus(200);
        $this->assertLessThan(0.3, $response->json('probability'));
    }

    /** @test */
    public function pre_match_status_changes_from_open_to_locked()
    {
        $preMatch = PreMatch::factory()->create([
            'group_id' => $this->group->id,
            'status' => 'OPEN',
        ]);

        $response = $this->actingAs($this->admin)->patchJson(
            "/api/pre-matches/{$preMatch->id}",
            ['status' => 'LOCKED']
        );

        $response->assertStatus(200);
        $this->assertDatabaseHas('pre_matches', [
            'id' => $preMatch->id,
            'status' => 'LOCKED',
        ]);
    }

    /** @test */
    public function pre_match_cannot_accept_propositions_when_locked()
    {
        $preMatch = PreMatch::factory()->create([
            'group_id' => $this->group->id,
            'status' => 'LOCKED',
        ]);

        $response = $this->actingAs($this->member1)->postJson(
            "/api/pre-matches/{$preMatch->id}/propositions",
            [
                'action' => '3+ goles de cabeza',
            ]
        );

        $response->assertStatus(403);
    }

    /** @test */
    public function list_pre_matches_for_group()
    {
        PreMatch::factory(3)->create(['group_id' => $this->group->id]);

        $response = $this->actingAs($this->member1)->getJson(
            "/api/groups/{$this->group->id}/pre-matches"
        );

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    /** @test */
    public function get_pre_match_with_propositions_and_votes()
    {
        $preMatch = PreMatch::factory()->create([
            'group_id' => $this->group->id,
            'status' => 'OPEN',
        ]);

        $prop = PreMatchProposition::factory()->create([
            'pre_match_id' => $preMatch->id,
            'user_id' => $this->member1->id,
        ]);

        PreMatchVote::factory(2)->create(['proposition_id' => $prop->id]);

        $response = $this->actingAs($this->member1)->getJson(
            "/api/pre-matches/{$preMatch->id}"
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'status',
            'penalty',
            'propositions' => [
                '*' => [
                    'id',
                    'action',
                    'votes' => ['*' => ['id', 'approved']],
                ],
            ],
        ]);
    }
}
