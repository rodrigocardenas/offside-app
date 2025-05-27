<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\GroupRoleService;
use App\Models\Group;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class GroupRoleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $groupRoleService;
    protected $group;
    protected $users;

    protected function setUp(): void
    {
        parent::setUp();

        $this->groupRoleService = new GroupRoleService();

        // Crear roles de prueba
        $roles = [
            ['name' => 'admin', 'display_name' => 'Administrador'],
            ['name' => 'member', 'display_name' => 'Miembro']
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }

        // Crear grupo y usuarios de prueba
        $this->group = Group::factory()->create();
        $this->users = User::factory()->count(3)->create();

        // Asignar usuarios al grupo
        foreach ($this->users as $user) {
            $this->group->users()->attach($user->id);
        }
    }

    /** @test */
    public function it_can_get_group_roles()
    {
        // Asignar roles a los usuarios
        $adminRole = Role::where('name', 'admin')->first();
        $memberRole = Role::where('name', 'member')->first();

        $this->users[0]->roles()->attach($adminRole->id);
        $this->users[1]->roles()->attach($memberRole->id);
        $this->users[2]->roles()->attach($memberRole->id);

        $roles = $this->groupRoleService->getGroupRoles($this->group);

        $this->assertCount(3, $roles);
        $this->assertTrue(Cache::has("group_{$this->group->id}_roles"));

        // Verificar que el primer usuario tiene rol de admin
        $this->assertEquals('admin', $roles[$this->users[0]->id][0]->name);

        // Verificar que los otros usuarios tienen rol de member
        $this->assertEquals('member', $roles[$this->users[1]->id][0]->name);
        $this->assertEquals('member', $roles[$this->users[2]->id][0]->name);
    }

    /** @test */
    public function it_can_assign_roles_to_users()
    {
        // Preparar roles
        $roles = [
            $this->users[0]->id => [['id' => 1, 'name' => 'admin']],
            $this->users[1]->id => [['id' => 2, 'name' => 'member']],
            $this->users[2]->id => [['id' => 2, 'name' => 'member']]
        ];

        $this->groupRoleService->assignRolesToUsers($this->group, $roles);

        // Verificar que los roles se asignaron correctamente
        foreach ($this->users as $user) {
            $user->load('roles');
            $this->assertCount(1, $user->roles);
        }
    }

    /** @test */
    public function it_handles_empty_roles()
    {
        $roles = [];
        $this->groupRoleService->assignRolesToUsers($this->group, $roles);

        foreach ($this->users as $user) {
            $user->load('roles');
            $this->assertCount(0, $user->roles);
        }
    }
}
