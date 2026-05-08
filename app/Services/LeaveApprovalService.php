<?php

namespace App\Services;

use App\Enums\ApprovalDecision;
use App\Enums\LeaveStatus;
use App\Models\ApprovalAction;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class LeaveApprovalService
{
    /**
     * Determine the initial status for a new leave request based on the submitter's role.
     */
    public function initialStatus(User $submitter): LeaveStatus
    {
        if ($submitter->hasRole('admin')) {
            return LeaveStatus::PendingSuperAdmin;
        }

        if ($submitter->hasRole('hr')) {
            return LeaveStatus::PendingAdmin;
        }

        return LeaveStatus::PendingHr;
    }

    /**
     * Determine whether the approval action is a bypass.
     *
     * For Admin: true when acting on an Employee request not in pending_admin (Admin's normal stage).
     * For SuperAdmin: true when acting outside pending_super_admin (any earlier stage).
     */
    public function isBypass(User $approver, LeaveRequest $request): bool
    {
        if ($approver->hasRole('super_admin')) {
            return $request->status !== LeaveStatus::PendingSuperAdmin;
        }

        $submitter = $request->user;

        if (! $submitter->hasRole('employee')) {
            return false;
        }

        if ($approver->hasRole('admin')) {
            return $request->status !== LeaveStatus::PendingAdmin;
        }

        return false;
    }

    /**
     * Determine whether the approver can approve the given leave request.
     */
    public function canApprove(User $approver, LeaveRequest $request): bool
    {
        // Self-approval prevention
        if ($approver->id === $request->user_id) {
            return false;
        }

        // Cannot act on terminal requests
        if ($request->status->isTerminal()) {
            return false;
        }

        if ($approver->hasRole('super_admin')) {
            return true;
        }

        $submitter = $request->user;
        $status = $request->status;

        // HR: can approve Employee requests in pending_hr
        if ($approver->hasRole('hr')) {
            return $submitter->hasRole('employee') && $status === LeaveStatus::PendingHr;
        }

        // Admin: can approve Employee requests in pending_hr (bypass) or pending_admin,
        //        and HR requests in pending_admin.
        //        Admin CANNOT approve Admin self-requests.
        if ($approver->hasRole('admin')) {
            if ($submitter->hasRole('admin')) {
                return false;
            }

            if ($submitter->hasRole('employee')) {
                return $status === LeaveStatus::PendingHr || $status === LeaveStatus::PendingAdmin;
            }

            if ($submitter->hasRole('hr')) {
                return $status === LeaveStatus::PendingAdmin;
            }

            return false;
        }

        return false;
    }

    /**
     * Determine whether the approver can reject the given leave request.
     * Same logic as canApprove.
     */
    public function canReject(User $approver, LeaveRequest $request): bool
    {
        return $this->canApprove($approver, $request);
    }

    /**
     * Approve a leave request, transitioning its status and recording an ApprovalAction.
     */
    public function approve(User $approver, LeaveRequest $request, ?string $comment = null): ApprovalAction
    {
        $isbypass = $this->isBypass($approver, $request);
        $newStatus = $this->resolveApproveStatus($approver, $request);

        $request->status = $newStatus;
        $request->save();

        return ApprovalAction::create([
            'leave_request_id' => $request->id,
            'user_id' => $approver->id,
            'decision' => ApprovalDecision::Approved,
            'comment' => $comment,
            'is_bypass' => $isbypass,
        ]);
    }

    /**
     * Reject a leave request, setting its status to rejected and recording an ApprovalAction.
     */
    public function reject(User $approver, LeaveRequest $request, string $reason, ?string $comment = null): ApprovalAction
    {
        $isbypass = $this->isBypass($approver, $request);

        $request->status = LeaveStatus::Rejected;
        $request->save();

        return ApprovalAction::create([
            'leave_request_id' => $request->id,
            'user_id' => $approver->id,
            'decision' => ApprovalDecision::Rejected,
            'comment' => $reason,
            'is_bypass' => $isbypass,
        ]);
    }

    /**
     * Cancel a leave request, setting its status to cancelled and recording the cancellation timestamp.
     */
    public function cancel(LeaveRequest $request): void
    {
        $request->status = LeaveStatus::Cancelled;
        $request->cancelled_at = now();
        $request->save();
    }

    /**
     * Return an Eloquent query builder scoped to leave requests the approver can act on.
     */
    public function queueFor(User $approver): Builder
    {
        $query = LeaveRequest::with('user')->where('user_id', '!=', $approver->id);

        if ($approver->hasRole('hr')) {
            return $query->whereHas('user', function (Builder $q): void {
                $q->role('employee');
            })->where('status', LeaveStatus::PendingHr->value);
        }

        if ($approver->hasRole('admin')) {
            return $query->where(function (Builder $q): void {
                // Employee requests in pending_hr or pending_admin
                $q->where(function (Builder $inner): void {
                    $inner->whereHas('user', function (Builder $u): void {
                        $u->role('employee');
                    })->whereIn('status', [
                        LeaveStatus::PendingHr->value,
                        LeaveStatus::PendingAdmin->value,
                    ]);
                })
                    // HR requests in pending_admin
                    ->orWhere(function (Builder $inner): void {
                        $inner->whereHas('user', function (Builder $u): void {
                            $u->role('hr');
                        })->where('status', LeaveStatus::PendingAdmin->value);
                    });
            })
                // Exclude Admin_Self_Requests (submitter is admin)
                ->whereDoesntHave('user', function (Builder $u): void {
                    $u->role('admin');
                });
        }

        if ($approver->hasRole('super_admin')) {
            return $query->whereNotIn('status', [
                LeaveStatus::Approved->value,
                LeaveStatus::Rejected->value,
                LeaveStatus::Cancelled->value,
            ]);
        }

        // Fallback: return empty result set
        return $query->whereRaw('1 = 0');
    }

    /**
     * Resolve the new status after an approve action based on the transition table.
     */
    private function resolveApproveStatus(User $approver, LeaveRequest $request): LeaveStatus
    {
        $submitter = $request->user;
        $status = $request->status;

        if ($approver->hasRole('hr') && $submitter->hasRole('employee') && $status === LeaveStatus::PendingHr) {
            return LeaveStatus::PendingAdmin;
        }

        if ($approver->hasRole('admin')) {
            if ($submitter->hasRole('employee')) {
                // Bypass (pending_hr) or normal (pending_admin) → approved
                return LeaveStatus::Approved;
            }

            if ($submitter->hasRole('hr') && $status === LeaveStatus::PendingAdmin) {
                return LeaveStatus::PendingSuperAdmin;
            }
        }

        if ($approver->hasRole('super_admin')) {
            // Employee (bypass), HR, or Admin in pending_super_admin → approved
            return LeaveStatus::Approved;
        }

        return LeaveStatus::Approved;
    }
}
