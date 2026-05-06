<?php

namespace App\Policies;

use App\Models\Skill;
use App\Models\User;

class SkillPolicy
{
    /**
     * Determine whether the user can view any skills.
     * Requirements: 8.1, 8.2
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'hr', 'employee']);
    }

    /**
     * Determine whether the user can view the skill.
     * Requirements: 8.1, 8.2
     */
    public function view(User $user, Skill $skill): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'hr', 'employee']);
    }

    /**
     * Determine whether the user can create skills.
     * Requirements: 1.6, 8.1
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'hr']);
    }

    /**
     * Determine whether the user can update the skill.
     * Requirements: 1.6, 8.1
     */
    public function update(User $user, Skill $skill): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'hr']);
    }

    /**
     * Determine whether the user can delete the skill.
     * Requirements: 2.3, 8.1
     */
    public function delete(User $user, Skill $skill): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'hr']);
    }

    /**
     * Determine whether the user can toggle the skill's active status.
     * Requirements: 8.1, 9.1
     */
    public function toggle(User $user, Skill $skill): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'hr']);
    }
}
