<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveApprovalService;

class LeaveRequestPolicy
{
    public function __construct(private readonly LeaveApprovalService $approvalService) {}

    /**
     * Any authenticated user may access the personal leave request index.
     * Requirement 12.1
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Employees, HR users, and Admins may submit leave requests.
     * Requirement 12.2
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['employee', 'hr', 'admin']);
    }

    /**
     * The owner may always view their own request.
     * Admin and Super Admin may view any request (for the all-requests listing).
     * Requirement 12.6
     */
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->id === $leaveRequest->user_id) {
            return true;
        }

        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Only the owner may cancel, and only when the request is not in a terminal status.
     * Requirements 9.1, 9.4
     */
    public function cancel(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->id !== $leaveRequest->user_id) {
            return false;
        }

        return ! $leaveRequest->status->isTerminal();
    }

    /**
     * Delegates to LeaveApprovalService to enforce role, stage, and self-approval rules.
     * Requirements 12.3–12.5, 12.8, 12.9
     */
    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->approvalService->canApprove($user, $leaveRequest);
    }

    /**
     * Delegates to LeaveApprovalService — same rules as approve.
     * Requirements 12.3–12.5, 12.8, 12.9
     */
    public function reject(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->approvalService->canReject($user, $leaveRequest);
    }

    /**
     * Only Admin and Super Admin may access the all-requests listing.
     * Requirement 10.6
     */
    public function viewAll(User $user): bool
    {
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * HR, Admin, and Super Admin may access the approval queue.
     * Requirements 10.3–10.5
     */
    public function viewApprovalQueue(User $user): bool
    {
        return $user->hasRole(['hr', 'admin', 'super_admin']);
    }
}
