<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any projects.
     * Requirements: 12.1, 12.4
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['hr', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can view the project.
     * Privileged users may always view; employees may view only their assigned projects.
     * Requirements: 8.2, 8.4, 12.3
     */
    public function view(User $user, Project $project): bool
    {
        if ($user->hasRole(['hr', 'admin', 'super_admin'])) {
            return true;
        }

        if ($user->hasRole('employee')) {
            return ProjectAssignment::where('project_id', $project->id)
                ->where('user_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create projects.
     * Requirements: 1.7, 12.1
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['hr', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can update the project.
     * Requirements: 2.4, 12.1
     */
    public function update(User $user, Project $project): bool
    {
        return $user->hasRole(['hr', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can delete the project.
     * Requirements: 2.4, 12.1
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->hasRole(['hr', 'admin', 'super_admin']);
    }
}
