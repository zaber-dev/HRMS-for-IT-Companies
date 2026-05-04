<?php

namespace App\Policies;

use App\Models\User;
use App\Support\RoleHierarchy;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    /**
     * Determine whether the actor can view any roles.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermissionTo('role.view');
    }

    /**
     * Determine whether the actor can create roles.
     */
    public function create(User $actor): bool
    {
        return $actor->hasPermissionTo('role.create');
    }

    /**
     * Determine whether the actor can update the role.
     */
    public function update(User $actor, Role $role): bool
    {
        return $actor->hasPermissionTo('role.update')
            && ! in_array($role->name, RoleHierarchy::BUILT_IN_ROLES, true);
    }

    /**
     * Determine whether the actor can delete the role.
     */
    public function delete(User $actor, Role $role): bool
    {
        return $actor->hasPermissionTo('role.delete')
            && ! in_array($role->name, RoleHierarchy::BUILT_IN_ROLES, true)
            && $role->users()->count() === 0;
    }

    /**
     * Determine whether the actor can manage permissions on the role.
     */
    public function managePermissions(User $actor, Role $role): bool
    {
        return $actor->hasPermissionTo('permission.manage')
            && $role->name !== 'super_admin';
    }
}
