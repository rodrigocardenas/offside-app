<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    /**
     * Determine whether the user can update the group.
     */
    public function update(User $user, Group $group): bool
    {
        // Solo el creador del grupo puede actualizarlo
        return $user->id === $group->created_by;
    }

    /**
     * Determine whether the user can delete the group.
     */
    public function delete(User $user, Group $group): bool
    {
        // Solo el creador del grupo puede eliminarlo
        return $user->id === $group->created_by;
    }

    /**
     * Determine whether the user can view the group summary.
     */
    public function viewSummary(User $user, Group $group): bool
    {
        // Solo creador del grupo o administradores pueden ver el resumen
        return $user->id === $group->created_by || $user->is_admin;
    }
}
