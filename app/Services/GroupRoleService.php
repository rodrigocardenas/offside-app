<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Group;
use App\Models\User;
use App\Models\GroupRole;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GroupRoleService
{
    public function getGroupRoles(Group $group)
    {
        $cacheKey = "group_{$group->id}_roles";
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($group) {
            return $group->users()->with('roles')->get();
        });
    }

    public function assignRole(User $user, Group $group, string $role)
    {
        $groupRole = GroupRole::updateOrCreate(
            [
                'user_id' => $user->id,
                'group_id' => $group->id,
            ],
            [
                'role' => $role,
            ]
        );

        Cache::forget("group_{$group->id}_roles");
        return $groupRole;
    }

    public function removeRole(User $user, Group $group)
    {
        GroupRole::where('user_id', $user->id)
            ->where('group_id', $group->id)
            ->delete();

        Cache::forget("group_{$group->id}_roles");
    }

    public function assignRolesToUsers($group, $roles)
    {
        foreach ($group->users as $user) {
            if (isset($roles[$user->id])) {
                $roleIds = collect($roles[$user->id])
                    ->pluck('id')
                    ->filter(function ($id) {
                        return is_numeric($id) && $id > 0;
                    });

                if ($roleIds->isNotEmpty()) {
                    $user->roles()->sync($roleIds);
                } else {
                    $user->roles()->detach();
                }
            } else {
                $user->roles()->detach();
            }
        }
    }
}
