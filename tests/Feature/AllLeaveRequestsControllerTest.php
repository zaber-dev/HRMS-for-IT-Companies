<?php

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create an active user with the given role.
 */
function allLeaveUser(string $role): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole($role);
}

// ---------------------------------------------------------------------------
// Access control — happy paths
// ---------------------------------------------------------------------------

describe('access control (happy paths)', function () {
    test('Admin can access /leave-requests/all', function () {
        $admin = allLeaveUser('admin');

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('leave-requests.all'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('leave/all'));
    });

    test('Super Admin can access /leave-requests/all', function () {
        $superAdmin = allLeaveUser('super_admin');

        $this->withoutVite()
            ->actingAs($superAdmin)
            ->get(route('leave-requests.all'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('leave/all'));
    });
});

// ---------------------------------------------------------------------------
// Access control — 403 for lower roles
// ---------------------------------------------------------------------------

describe('access control (403)', function () {
    test('HR receives 403 on /leave-requests/all', function () {
        $hr = allLeaveUser('hr');

        $this->actingAs($hr)
            ->get(route('leave-requests.all'))
            ->assertForbidden();
    });

    test('Employee receives 403 on /leave-requests/all', function () {
        $employee = allLeaveUser('employee');

        $this->actingAs($employee)
            ->get(route('leave-requests.all'))
            ->assertForbidden();
    });

    test('unauthenticated user is redirected to login', function () {
        $this->get(route('leave-requests.all'))
            ->assertRedirect(route('login'));
    });
});

// ---------------------------------------------------------------------------
// Filters
// ---------------------------------------------------------------------------

describe('filters', function () {
    test('status filter returns only matching requests', function () {
        $admin = allLeaveUser('admin');
        $employee = allLeaveUser('employee');

        LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'status' => LeaveStatus::PendingHr,
        ]);

        LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'status' => LeaveStatus::Approved,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
        ]);

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('leave-requests.all', ['status' => 'pending_hr']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('leave/all')
                ->where('leaveRequests.total', 1)
                ->where('leaveRequests.data.0.status', 'pending_hr')
            );
    });

    test('status filter with approved returns only approved requests', function () {
        $admin = allLeaveUser('admin');
        $employee = allLeaveUser('employee');

        LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'status' => LeaveStatus::PendingHr,
        ]);

        LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'status' => LeaveStatus::Approved,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
        ]);

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('leave-requests.all', ['status' => 'approved']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('leave/all')
                ->where('leaveRequests.total', 1)
                ->where('leaveRequests.data.0.status', 'approved')
            );
    });

    test('start_date filter returns only requests on or after the given date', function () {
        $admin = allLeaveUser('admin');
        $employee = allLeaveUser('employee');

        // Request starting before the filter date
        LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'status' => LeaveStatus::PendingHr,
        ]);

        // Request starting on or after the filter date
        LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
            'status' => LeaveStatus::PendingAdmin,
        ]);

        $filterDate = now()->addDays(8)->toDateString();

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('leave-requests.all', ['start_date' => $filterDate]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('leave/all')
                ->where('leaveRequests.total', 1)
            );
    });

    test('end_date filter returns only requests ending on or before the given date', function () {
        $admin = allLeaveUser('admin');
        $employee = allLeaveUser('employee');

        // Request ending before the filter date
        LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'status' => LeaveStatus::PendingHr,
        ]);

        // Request ending after the filter date
        LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
            'status' => LeaveStatus::PendingAdmin,
        ]);

        $filterDate = now()->addDays(6)->toDateString();

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('leave-requests.all', ['end_date' => $filterDate]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('leave/all')
                ->where('leaveRequests.total', 1)
            );
    });

    test('combined start_date and end_date filter returns only requests within the range', function () {
        $admin = allLeaveUser('admin');
        $employee = allLeaveUser('employee');

        // Inside range
        LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'status' => LeaveStatus::PendingHr,
        ]);

        // Outside range (starts too early)
        LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'status' => LeaveStatus::Approved,
        ]);

        // Outside range (ends too late)
        LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(12)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'status' => LeaveStatus::PendingAdmin,
        ]);

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('leave-requests.all', [
                'start_date' => now()->addDays(4)->toDateString(),
                'end_date' => now()->addDays(10)->toDateString(),
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('leave/all')
                ->where('leaveRequests.total', 1)
            );
    });

    test('no filters returns all requests', function () {
        $admin = allLeaveUser('admin');
        $employee = allLeaveUser('employee');
        $hr = allLeaveUser('hr');

        LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'status' => LeaveStatus::PendingHr,
        ]);

        LeaveRequest::factory()->create([
            'user_id' => $hr->id,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
            'status' => LeaveStatus::PendingAdmin,
        ]);

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('leave-requests.all'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('leave/all')
                ->where('leaveRequests.total', 2)
            );
    });

    test('filters prop is passed back to the view', function () {
        $admin = allLeaveUser('admin');

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('leave-requests.all', ['status' => 'approved']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('leave/all')
                ->where('filters.status', 'approved')
            );
    });

    test('statuses prop is passed to the view', function () {
        $admin = allLeaveUser('admin');

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('leave-requests.all'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('leave/all')
                ->has('statuses')
            );
    });
});
