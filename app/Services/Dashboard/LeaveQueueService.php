<?php

namespace App\Services\Dashboard;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\User;

class LeaveQueueService
{
    /**
     * Get the count of leave requests awaiting action for the given role.
     *
     * - `hr`          → count of `pending_hr` requests
     * - `admin`       → count of `pending_admin` requests, excluding requests submitted by other admin users
     * - `super_admin` → count of non-terminal requests
     */
    public function get(string $role): int
    {
        return match ($role) {
            'hr' => LeaveRequest::where('status', LeaveStatus::PendingHr)->count(),

            'admin' => (function (): int {
                $adminUserIds = User::role('admin')->pluck('id');

                return LeaveRequest::where('status', LeaveStatus::PendingAdmin)
                    ->whereNotIn('user_id', $adminUserIds)
                    ->count();
            })(),

            'super_admin' => LeaveRequest::whereNotIn('status', [
                LeaveStatus::Approved->value,
                LeaveStatus::Rejected->value,
                LeaveStatus::Cancelled->value,
            ])->count(),

            default => 0,
        };
    }
}
