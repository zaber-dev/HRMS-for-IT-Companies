<?php

namespace App\Enums;

enum LeaveStatus: string
{
    case PendingHr = 'pending_hr';
    case PendingAdmin = 'pending_admin';
    case PendingSuperAdmin = 'pending_super_admin';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Approved, self::Rejected, self::Cancelled]);
    }
}
