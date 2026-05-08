<?php

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\Dashboard\PtoAlertService;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->service = app(PtoAlertService::class);
});

function makeEmployee(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
        'bench_status' => 'on_bench',
    ])->assignRole('employee');
}

function leaveInYear(User $user, string $status, int $days, int $startOffset = 1): void
{
    LeaveRequest::factory()->create([
        'user_id' => $user->id,
        'start_date' => now()->startOfYear()->addDays($startOffset)->toDateString(),
        'end_date' => now()->startOfYear()->addDays($startOffset + $days - 1)->toDateString(),
        'status' => $status,
        'submitted_at' => now(),
    ]);
}

// ---------------------------------------------------------------------------
// Threshold filtering
// ---------------------------------------------------------------------------

test('employee at exactly the threshold is included', function () {
    $employee = makeEmployee();
    leaveInYear($employee, LeaveStatus::Approved->value, 14); // exactly 14 days

    $results = $this->service->get(14);

    $ids = array_column($results, 'user_id');
    expect($ids)->toContain($employee->id);
});

test('employee below the threshold is excluded', function () {
    $employee = makeEmployee();
    leaveInYear($employee, LeaveStatus::Approved->value, 13); // 13 days — below threshold

    $results = $this->service->get(14);

    $ids = array_column($results, 'user_id');
    expect($ids)->not->toContain($employee->id);
});

test('employee above the threshold is included', function () {
    $employee = makeEmployee();
    leaveInYear($employee, LeaveStatus::Approved->value, 20);

    $results = $this->service->get(14);

    $ids = array_column($results, 'user_id');
    expect($ids)->toContain($employee->id);
});

// ---------------------------------------------------------------------------
// Leave day summation
// ---------------------------------------------------------------------------

test('leave days are summed across multiple requests in the current year', function () {
    $employee = makeEmployee();
    leaveInYear($employee, LeaveStatus::Approved->value, 8, 1);   // 8 days
    leaveInYear($employee, LeaveStatus::Approved->value, 8, 20);  // 8 days → total 16

    $results = $this->service->get(14);

    $entry = collect($results)->firstWhere('user_id', $employee->id);
    expect($entry)->not->toBeNull();
    expect($entry['total_leave_days'])->toBe(16);
});

test('leave requests from previous year are not counted', function () {
    $employee = makeEmployee();

    // Previous year request — should not count
    LeaveRequest::factory()->create([
        'user_id' => $employee->id,
        'start_date' => now()->subYear()->startOfYear()->addDays(1)->toDateString(),
        'end_date' => now()->subYear()->startOfYear()->addDays(20)->toDateString(),
        'status' => LeaveStatus::Approved->value,
        'submitted_at' => now()->subYear(),
    ]);

    $results = $this->service->get(14);

    $ids = array_column($results, 'user_id');
    expect($ids)->not->toContain($employee->id);
});

test('all four countable statuses contribute to total_leave_days', function () {
    $employee = makeEmployee();
    leaveInYear($employee, LeaveStatus::Approved->value, 4, 1);
    leaveInYear($employee, LeaveStatus::PendingHr->value, 4, 10);
    leaveInYear($employee, LeaveStatus::PendingAdmin->value, 4, 20);
    leaveInYear($employee, LeaveStatus::PendingSuperAdmin->value, 4, 30);
    // total = 16 days

    $results = $this->service->get(14);

    $entry = collect($results)->firstWhere('user_id', $employee->id);
    expect($entry)->not->toBeNull();
    expect($entry['total_leave_days'])->toBe(16);
});

test('rejected and cancelled leave requests are not counted', function () {
    $employee = makeEmployee();
    leaveInYear($employee, LeaveStatus::Rejected->value, 20, 1);
    leaveInYear($employee, LeaveStatus::Cancelled->value, 20, 30);

    $results = $this->service->get(14);

    $ids = array_column($results, 'user_id');
    expect($ids)->not->toContain($employee->id);
});

// ---------------------------------------------------------------------------
// Sorting
// ---------------------------------------------------------------------------

test('results are sorted descending by total_leave_days', function () {
    $employeeA = makeEmployee();
    $employeeB = makeEmployee();
    leaveInYear($employeeA, LeaveStatus::Approved->value, 14, 1);
    leaveInYear($employeeB, LeaveStatus::Approved->value, 20, 1);

    $results = $this->service->get(14);

    expect($results[0]['user_id'])->toBe($employeeB->id);
    expect($results[1]['user_id'])->toBe($employeeA->id);
});

// ---------------------------------------------------------------------------
// Role exclusion
// ---------------------------------------------------------------------------

test('HR users are excluded from results', function () {
    $hr = User::factory()->create(['is_active' => true, 'must_change_password' => false])->assignRole('hr');
    leaveInYear($hr, LeaveStatus::Approved->value, 20, 1);

    $results = $this->service->get(14);

    $ids = array_column($results, 'user_id');
    expect($ids)->not->toContain($hr->id);
});

test('admin users are excluded from results', function () {
    $admin = User::factory()->create(['is_active' => true, 'must_change_password' => false])->assignRole('admin');
    leaveInYear($admin, LeaveStatus::Approved->value, 20, 1);

    $results = $this->service->get(14);

    $ids = array_column($results, 'user_id');
    expect($ids)->not->toContain($admin->id);
});

// ---------------------------------------------------------------------------
// pending_requests_count
// ---------------------------------------------------------------------------

test('pending_requests_count reflects only pending-status requests', function () {
    $employee = makeEmployee();
    leaveInYear($employee, LeaveStatus::Approved->value, 10, 1);
    leaveInYear($employee, LeaveStatus::PendingHr->value, 5, 20);
    leaveInYear($employee, LeaveStatus::PendingAdmin->value, 5, 30);

    $results = $this->service->get(14);

    $entry = collect($results)->firstWhere('user_id', $employee->id);
    expect($entry['pending_requests_count'])->toBe(2);
});
