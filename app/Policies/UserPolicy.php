<?php

namespace App\Policies;

use App\Models\User;
use App\Support\RoleHierarchy;

class UserPolicy
{
    /**
     * Determine whether the actor can view any users.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermissionTo('user.view');
    }

    /**
     * Determine whether the actor can create users.
     */
    public function create(User $actor): bool
    {
        return $actor->hasPermissionTo('user.create');
    }

    /**
     * Determine whether the actor can update the target user.
     */
    public function update(User $actor, User $target): bool
    {
        $actorRole = $actor->getRoleNames()->first();
        $targetRole = $target->getRoleNames()->first();

        return $actor->hasPermissionTo('user.update')
            && RoleHierarchy::isAbove($actorRole, $targetRole);
    }

    /**
     * Determine whether the actor can delete (deactivate) the target user.
     */
    public function delete(User $actor, User $target): bool
    {
        if ($target->hasRole('super_admin')) {
            return false;
        }

        $actorRole = $actor->getRoleNames()->first();
        $targetRole = $target->getRoleNames()->first();

        return $actor->hasPermissionTo('user.delete')
            && RoleHierarchy::isAbove($actorRole, $targetRole);
    }
}
