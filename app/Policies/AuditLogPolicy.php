<?php

namespace App\Policies;

use App\Models\User;

class AuditLogPolicy
{
    /**
     * Determine whether the actor can view any audit log entries.
     * Only users with the `audit-log.view` permission (super_admin) are allowed.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermissionTo('audit-log.view');
    }
}
