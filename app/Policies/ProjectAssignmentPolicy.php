<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\User;

class ProjectAssignmentPolicy
{
    /**
     * Determine whether the user can create assignments for the given project.
     * Requirements: 5.8, 12.2
     */
    public function create(User $user, Project $project): bool
    {
        return $user->hasRole(['hr', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can update the assignment.
     * Requirements: 11.4, 12.2
     */
    public function update(User $user, ProjectAssignment $assignment): bool
    {
        return $user->hasRole(['hr', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can delete the assignment.
     * Requirements: 10.5, 12.2
     */
    public function delete(User $user, ProjectAssignment $assignment): bool
    {
        return $user->hasRole(['hr', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can mark the assignment as complete.
     * Only the employee who owns the assignment may mark it complete.
     * Requirements: 7.4
     */
    public function markComplete(User $user, ProjectAssignment $assignment): bool
    {
        return $user->hasRole('employee') && $user->id === $assignment->user_id;
    }
}
