<?php

namespace App\Policies;

use App\Models\User;

class DashboardPolicy
{
    /**
     * Determine whether the user can view the HR dashboard (company-wide analytics).
     * Grants access to users with the `hr`, `admin`, or `super_admin` role.
     * Requirement 14.1
     */
    public function viewHr(User $user): bool
    {
        return $user->hasRole(['hr', 'admin', 'super_admin']);
    }

    /**
     * Determine whether the user can view the Employee dashboard (personal stats).
     * Grants access to users with the `employee` role only.
     * Requirement 14.2
     */
    public function viewEmployee(User $user): bool
    {
        return $user->hasRole('employee');
    }
}
