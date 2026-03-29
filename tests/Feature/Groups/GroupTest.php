<?php

namespace Tests\Feature\Groups;

use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can create a group
     */
    public function test_user_can_create_a_group(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/groups', [
            'name' => 'Test Group',
            'description' => 'A test group for competitions',
            'is_public' => false,
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'name', 'description', 'owner_id']);

        $this->assertDatabaseHas('groups', [
            'name' => 'Test Group',
            'owner_id' => $user->id,
        ]);
    }

    /**
     * Test user can join a public group
     */
    public function test_user_can_join_a_public_group(): void
    {
        $owner = User::factory()->create();
        $joiner = User::factory()->create();

        $group = Group::factory()->create([
            'owner_id' => $owner->id,
            'is_public' => true,
        ]);

        $response = $this->actingAs($joiner)->postJson("/api/groups/{$group->id}/join");

        $response->assertStatus(200);
        $this->assertDatabaseHas('group_user', [
            'group_id' => $group->id,
            'user_id' => $joiner->id,
        ]);
    }

    /**
     * Test user cannot join a private group without invitation
     */
    public function test_user_cannot_join_private_group_without_invitation(): void
    {
        $owner = User::factory()->create();
        $joiner = User::factory()->create();

        $group = Group::factory()->create([
            'owner_id' => $owner->id,
            'is_public' => false,
        ]);

        $response = $this->actingAs($joiner)->postJson("/api/groups/{$group->id}/join");

        $response->assertStatus(403);
    }

    /**
     * Test group owner can invite users
     */
    public function test_group_owner_can_invite_users(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();

        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)->postJson("/api/groups/{$group->id}/invite", [
            'user_id' => $invitee->id,
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test user can view group members
     */
    public function test_user_can_view_group_members(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()
            ->hasAttached($user)
            ->create();

        $response = $this->actingAs($user)->getJson("/api/groups/{$group->id}/members");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => ['id', 'name', 'email'],
                 ]);
    }

    /**
     * Test user can view group's leaderboard
     */
    public function test_user_can_view_group_leaderboard(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()
            ->hasAttached($user)
            ->create();

        $response = $this->actingAs($user)->getJson("/api/groups/{$group->id}/leaderboard");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => ['user_id', 'position', 'points'],
                 ]);
    }
}
