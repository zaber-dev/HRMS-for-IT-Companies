<?php

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\Dashboard\LeaveQueueService;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->service = app(LeaveQueueService::class);
});

function makeQueueUser(string $role): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole($role);
}

function queueLeave(User $user, LeaveStatus $status): LeaveRequest
{
    return LeaveRequest::factory()->create([
        'user_id' => $user->id,
        'status' => $status,
        'submitted_at' => now(),
    ]);
}

// ---------------------------------------------------------------------------
// HR role
// ---------------------------------------------------------------------------

test('HR role returns count of pending_hr requests only', function () {
    $employee = makeQueueUser('employee');

    queueLeave($employee, LeaveStatus::PendingHr);
    queueLeave($employee, LeaveStatus::PendingHr);
    queueLeave($employee, LeaveStatus::PendingAdmin);       // should not count
    queueLeave($employee, LeaveStatus::PendingSuperAdmin);  // should not count

    expect($this->service->get('hr'))->toBe(2);
});

test('HR role returns zero when no pending_hr requests exist', function () {
    $employee = makeQueueUser('employee');
    queueLeave($employee, LeaveStatus::PendingAdmin);

    expect($this->service->get('hr'))->toBe(0);
});

// ---------------------------------------------------------------------------
// Admin role
// ---------------------------------------------------------------------------

test('Admin role returns count of pending_admin requests from non-admin users', function () {
    $employee = makeQueueUser('employee');

    queueLeave($employee, LeaveStatus::PendingAdmin);
    queueLeave($employee, LeaveStatus::PendingAdmin);

    expect($this->service->get('admin'))->toBe(2);
});

test('Admin role excludes pending_admin requests submitted by admin users', function () {
    $employee = makeQueueUser('employee');
    $otherAdmin = makeQueueUser('admin');

    queueLeave($employee, LeaveStatus::PendingAdmin);   // should count
    queueLeave($otherAdmin, LeaveStatus::PendingAdmin); // should NOT count

    expect($this->service->get('admin'))->toBe(1);
});

test('Admin role excludes all admin self-requests leaving zero', function () {
    $admin1 = makeQueueUser('admin');
    $admin2 = makeQueueUser('admin');

    queueLeave($admin1, LeaveStatus::PendingAdmin);
    queueLeave($admin2, LeaveStatus::PendingAdmin);

    expect($this->service->get('admin'))->toBe(0);
});

test('Admin role does not count pending_hr or pending_super_admin requests', function () {
    $employee = makeQueueUser('employee');

    queueLeave($employee, LeaveStatus::PendingHr);
    queueLeave($employee, LeaveStatus::PendingSuperAdmin);

    expect($this->service->get('admin'))->toBe(0);
});

// ---------------------------------------------------------------------------
// Super Admin role
// ---------------------------------------------------------------------------

test('Super Admin role returns count of all pending requests', function () {
    $employee = makeQueueUser('employee');

    queueLeave($employee, LeaveStatus::PendingSuperAdmin);
    queueLeave($employee, LeaveStatus::PendingSuperAdmin);
    queueLeave($employee, LeaveStatus::PendingHr);
    queueLeave($employee, LeaveStatus::PendingAdmin);

    expect($this->service->get('super_admin'))->toBe(4);
});

test('Super Admin role returns zero when no pending requests exist', function () {
    $employee = makeQueueUser('employee');
    queueLeave($employee, LeaveStatus::Approved);

    expect($this->service->get('super_admin'))->toBe(0);
});

// ---------------------------------------------------------------------------
// Unknown role
// ---------------------------------------------------------------------------

test('unknown role returns zero', function () {
    $employee = makeQueueUser('employee');
    queueLeave($employee, LeaveStatus::PendingHr);

    expect($this->service->get('unknown_role'))->toBe(0);
});
