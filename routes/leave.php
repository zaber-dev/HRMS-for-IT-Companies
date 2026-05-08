<?php

use App\Http\Controllers\Leave\AllLeaveRequestsController;
use App\Http\Controllers\Leave\ApprovalController;
use App\Http\Controllers\Leave\LeaveRequestController;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

Route::prefix('leave-requests')
    ->name('leave-requests.')
    ->middleware(['auth', 'verified', EnsureUserIsActive::class, EnsurePasswordChanged::class])
    ->group(function () {
        // Personal leave requests
        Route::get('/', [LeaveRequestController::class, 'index'])->name('index');
        Route::get('/create', [LeaveRequestController::class, 'create'])->name('create');
        Route::post('/', [LeaveRequestController::class, 'store'])->name('store');

        // Approval queue — MUST be before /{leaveRequest} to avoid wildcard capture
        Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::get('/approvals/{leaveRequest}/approve', [ApprovalController::class, 'showApprove'])->name('approvals.approve.show');
        Route::post('/approvals/{leaveRequest}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
        Route::get('/approvals/{leaveRequest}/reject', [ApprovalController::class, 'showReject'])->name('approvals.reject.show');
        Route::post('/approvals/{leaveRequest}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');

        // All requests (Admin/Super Admin) — MUST be before /{leaveRequest} to avoid wildcard capture
        Route::get('/all', [AllLeaveRequestsController::class, 'index'])->name('all');

        // Wildcard routes last
        Route::get('/{leaveRequest}', [LeaveRequestController::class, 'show'])->name('show');
        Route::delete('/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])->name('cancel');
    });
