<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupSummaryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que solo el creador puede ver el resumen
     */
    public function test_only_creator_can_view_summary(): void
    {
        $creator = User::factory()->create();
        $group = Group::factory()->create(['created_by' => $creator->id]);

        // Creador puede ver
        $response = $this->actingAs($creator)
            ->get("/groups/{$group->id}/summary");
        
        if ($response->status() !== 200) {
            \Log::error('Response error:', ['status' => $response->status(), 'exception' => $response->exception]);
        }
        
        $response->assertStatus(200)
            ->assertViewIs('groups.summary');

        // Otro usuario no puede ver
        $other = User::factory()->create();
        $this->actingAs($other)
            ->get("/groups/{$group->id}/summary")
            ->assertStatus(403);
    }

    /**
     * Test que el total de puntos se muestra correctamente
     */
    public function test_total_points_display_is_correct(): void
    {
        $creator = User::factory()->create();
        $group = Group::factory()->create(['created_by' => $creator->id]);

        // Agregar usuarios con puntos
        $group->users()->attach([
            User::factory()->create()->id => ['points' => 100],
            User::factory()->create()->id => ['points' => 200],
            User::factory()->create()->id => ['points' => 300],
        ]);

        // Actualizar el total
        $group->update(['total_points' => 600]);

        $response = $this->actingAs($creator)
            ->get("/groups/{$group->id}/summary");

        $response->assertStatus(200);
        $this->assertEquals(600, $response->viewData('stats')['total_points']);
    }

    /**
     * Test que el top 10 está ordenado correctamente
     */
    public function test_top_members_are_ordered_correctly(): void
    {
        $creator = User::factory()->create();
        $group = Group::factory()->create(['created_by' => $creator->id]);

        $users = User::factory(5)->create();
        
        $users->each(function ($user, $index) use ($group) {
            $points = (5 - $index) * 100; // 500, 400, 300, 200, 100
            $group->users()->attach($user->id, ['points' => $points]);
        });

        $response = $this->actingAs($creator)
            ->get("/groups/{$group->id}/summary");

        $topMembers = $response->viewData('stats')['top_members'];
        
        $this->assertEquals(500, $topMembers[0]->total_points);
        $this->assertEquals(400, $topMembers[1]->total_points);
        $this->assertEquals(100, $topMembers[4]->total_points);
    }

    /**
     * Test que admin puede ver el resumen aunque no sea creador
     */
    public function test_admin_can_view_summary_not_creator(): void
    {
        $creator = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $group = Group::factory()->create(['created_by' => $creator->id]);

        $response = $this->actingAs($admin)
            ->get("/groups/{$group->id}/summary");

        $response->assertStatus(200);
    }

    /**
     * Test que las estadísticas se calculan correctamente
     */
    public function test_statistics_are_calculated_correctly(): void
    {
        $creator = User::factory()->create();
        $group = Group::factory()->create(['created_by' => $creator->id]);

        // Agregar usuarios con puntos específicos: 0, 10, 20, 30, 40
        $group->users()->attach([
            User::factory()->create()->id => ['points' => 0],
            User::factory()->create()->id => ['points' => 10],
            User::factory()->create()->id => ['points' => 20],
            User::factory()->create()->id => ['points' => 30],
            User::factory()->create()->id => ['points' => 40],
        ]);

        $response = $this->actingAs($creator)
            ->get("/groups/{$group->id}/summary");

        $stats = $response->viewData('stats')['member_stats'];

        // Promedio: (0 + 10 + 20 + 30 + 40) / 5 = 20
        $this->assertEquals(20, $stats['avg_points']);
        // Max: 40
        $this->assertEquals(40, $stats['max_points']);
        // Min: 0
        $this->assertEquals(0, $stats['min_points']);
        // Mediana: 20
        $this->assertEquals(20, $stats['median_points']);
    }
}
