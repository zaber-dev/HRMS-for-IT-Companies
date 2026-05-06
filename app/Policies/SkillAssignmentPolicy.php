<?php

namespace App\Policies;

use App\Enums\AssignmentSource;
use App\Models\SkillAssignment;
use App\Models\User;

class SkillAssignmentPolicy
{
    /**
     * Determine whether the actor can assign a skill to the target user.
     * Requirements: 5.2, 8.3, 8.4
     */
    public function create(User $actor, User $target): bool
    {
        return $actor->id === $target->id || $actor->hasRole(['super_admin', 'admin', 'hr']);
    }

    /**
     * Determine whether the actor can remove a skill assignment from the target user.
     * Requirements: 6.6, 7.5, 8.3, 8.4
     *
     * Note: parameter order matches what Laravel passes when calling
     * $this->authorize('delete', [$assignment, $user]) — the first array element
     * is used for policy resolution AND passed as the first extra argument.
     */
    public function delete(User $actor, SkillAssignment $assignment, User $target): bool
    {
        $isSelfRemovingSelf = $actor->id === $target->id && $assignment->source === AssignmentSource::Self;

        return $isSelfRemovingSelf || $actor->hasRole(['super_admin', 'admin', 'hr']);
    }
}
