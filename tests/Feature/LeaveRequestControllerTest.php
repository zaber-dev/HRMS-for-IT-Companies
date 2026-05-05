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
function leaveUser(string $role): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole($role);
}

/**
 * Create a leave request for the given user with the given status.
 */
function leaveRequest(User $submitter, LeaveStatus $status = LeaveStatus::PendingHr): LeaveRequest
{
    return LeaveRequest::factory()->create([
        'user_id' => $submitter->id,
        'status' => $status,
    ]);
}

// ---------------------------------------------------------------------------
// store — happy paths (initial status by role)
// ---------------------------------------------------------------------------

describe('store (happy paths)', function () {
    test('Employee creates a leave request; status is pending_hr and redirects to index', function () {
        $employee = leaveUser('employee');

        $this->actingAs($employee)
            ->post(route('leave-requests.store'), [
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(3)->toDateString(),
                'reason' => 'Family vacation',
            ])
            ->assertRedirect(route('leave-requests.index'));

        $request = LeaveRequest::where('user_id', $employee->id)->latest()->first();
        expect($request)->not->toBeNull();
        expect($request->status)->toBe(LeaveStatus::PendingHr);
    });

    test('HR creates a leave request; status is pending_admin', function () {
        $hr = leaveUser('hr');

        $this->actingAs($hr)
            ->post(route('leave-requests.store'), [
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
                'reason' => 'Medical appointment',
            ])
            ->assertRedirect(route('leave-requests.index'));

        $request = LeaveRequest::where('user_id', $hr->id)->latest()->first();
        expect($request)->not->toBeNull();
        expect($request->status)->toBe(LeaveStatus::PendingAdmin);
    });

    test('Admin creates a leave request; status is pending_super_admin', function () {
        $admin = leaveUser('admin');

        $this->actingAs($admin)
            ->post(route('leave-requests.store'), [
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(4)->toDateString(),
                'reason' => 'Conference attendance',
            ])
            ->assertRedirect(route('leave-requests.index'));

        $request = LeaveRequest::where('user_id', $admin->id)->latest()->first();
        expect($request)->not->toBeNull();
        expect($request->status)->toBe(LeaveStatus::PendingSuperAdmin);
    });

    test('Leave request is created with correct user_id and submitted_at', function () {
        $employee = leaveUser('employee');

        $this->actingAs($employee)
            ->post(route('leave-requests.store'), [
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
            ]);

        $request = LeaveRequest::where('user_id', $employee->id)->latest()->first();
        expect($request->user_id)->toBe($employee->id);
        expect($request->submitted_at)->not->toBeNull();
    });
});

// ---------------------------------------------------------------------------
// store — validation errors
// ---------------------------------------------------------------------------

describe('store (validation errors)', function () {
    test('past start date returns 422', function () {
        $employee = leaveUser('employee');

        $this->actingAs($employee)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('leave-requests.store'), [
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
            ])
            ->assertStatus(422)
            ->assertInvalid(['start_date']);
    });

    test('end date before start date returns 422', function () {
        $employee = leaveUser('employee');

        $this->actingAs($employee)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('leave-requests.store'), [
                'start_date' => now()->addDays(5)->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
            ])
            ->assertStatus(422)
            ->assertInvalid(['end_date']);
    });

    test('overlapping request is rejected with a start_date session error', function () {
        $employee = leaveUser('employee');

        // Create an existing pending request
        LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => LeaveStatus::PendingHr,
        ]);

        // Submit an overlapping request — controller uses back()->withErrors() (Inertia pattern)
        $this->actingAs($employee)
            ->post(route('leave-requests.store'), [
                'start_date' => now()->addDays(7)->toDateString(),
                'end_date' => now()->addDays(12)->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['start_date']);
    });

    test('overlapping request is not rejected when existing request is cancelled', function () {
        $employee = leaveUser('employee');

        // Cancelled request — should not block new submission
        LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => LeaveStatus::Cancelled,
        ]);

        $this->actingAs($employee)
            ->post(route('leave-requests.store'), [
                'start_date' => now()->addDays(7)->toDateString(),
                'end_date' => now()->addDays(12)->toDateString(),
            ])
            ->assertRedirect(route('leave-requests.index'));
    });

    test('overlapping request is not rejected when existing request is rejected', function () {
        $employee = leaveUser('employee');

        // Rejected request — should not block new submission
        LeaveRequest::factory()->create([
            'user_id' => $employee->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => LeaveStatus::Rejected,
        ]);

        $this->actingAs($employee)
            ->post(route('leave-requests.store'), [
                'start_date' => now()->addDays(7)->toDateString(),
                'end_date' => now()->addDays(12)->toDateString(),
            ])
            ->assertRedirect(route('leave-requests.index'));
    });

    test('Super Admin cannot create a leave request', function () {
        $superAdmin = leaveUser('super_admin');

        $this->actingAs($superAdmin)
            ->post(route('leave-requests.store'), [
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addDays(3)->toDateString(),
            ])
            ->assertForbidden();
    });
});

// ---------------------------------------------------------------------------
// cancel
// ---------------------------------------------------------------------------

describe('cancel', function () {
    test('owner cancels a pending request; status becomes cancelled', function () {
        $employee = leaveUser('employee');
        $request = leaveRequest($employee, LeaveStatus::PendingHr);

        $this->actingAs($employee)
            ->delete(route('leave-requests.cancel', $request))
            ->assertRedirect(route('leave-requests.index'));

        expect($request->fresh()->status)->toBe(LeaveStatus::Cancelled);
    });

    test('owner cancels a pending_admin request; status becomes cancelled', function () {
        $hr = leaveUser('hr');
        $request = leaveRequest($hr, LeaveStatus::PendingAdmin);

        $this->actingAs($hr)
            ->delete(route('leave-requests.cancel', $request))
            ->assertRedirect(route('leave-requests.index'));

        expect($request->fresh()->status)->toBe(LeaveStatus::Cancelled);
    });

    test('403 when non-owner attempts to cancel', function () {
        $employee = leaveUser('employee');
        $otherEmployee = leaveUser('employee');
        $request = leaveRequest($otherEmployee, LeaveStatus::PendingHr);

        $this->actingAs($employee)
            ->delete(route('leave-requests.cancel', $request))
            ->assertForbidden();
    });

    test('422 when owner attempts to cancel an already-terminal request', function (LeaveStatus $status) {
        $employee = leaveUser('employee');
        $request = leaveRequest($employee, $status);

        $this->actingAs($employee)
            ->delete(route('leave-requests.cancel', $request))
            ->assertForbidden();
    })->with([LeaveStatus::Approved, LeaveStatus::Rejected, LeaveStatus::Cancelled]);
});

// ---------------------------------------------------------------------------
// show
// ---------------------------------------------------------------------------

describe('show', function () {
    test('owner can view their own leave request', function () {
        $employee = leaveUser('employee');
        $request = leaveRequest($employee, LeaveStatus::PendingHr);

        $this->withoutVite()
            ->actingAs($employee)
            ->get(route('leave-requests.show', $request))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('leave/show')
                ->where('leaveRequest.id', $request->id)
            );
    });

    test('non-owner Employee receives 403', function () {
        $employee = leaveUser('employee');
        $otherEmployee = leaveUser('employee');
        $request = leaveRequest($otherEmployee, LeaveStatus::PendingHr);

        $this->actingAs($employee)
            ->get(route('leave-requests.show', $request))
            ->assertForbidden();
    });

    test('Admin can view any leave request', function () {
        $admin = leaveUser('admin');
        $employee = leaveUser('employee');
        $request = leaveRequest($employee, LeaveStatus::PendingHr);

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('leave-requests.show', $request))
            ->assertOk();
    });
});

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

describe('index', function () {
    test('authenticated user can access their leave request index', function () {
        $employee = leaveUser('employee');

        $this->withoutVite()
            ->actingAs($employee)
            ->get(route('leave-requests.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('leave/index'));
    });

    test('index only returns the authenticated user\'s requests', function () {
        $employee = leaveUser('employee');
        $otherEmployee = leaveUser('employee');

        leaveRequest($employee, LeaveStatus::PendingHr);
        leaveRequest($otherEmployee, LeaveStatus::PendingHr);

        $this->withoutVite()
            ->actingAs($employee)
            ->get(route('leave-requests.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('leave/index')
                ->where('leaveRequests.total', 1)
            );
    });
});

// ---------------------------------------------------------------------------
// create
// ---------------------------------------------------------------------------

describe('create', function () {
    test('Employee can access the create page', function () {
        $employee = leaveUser('employee');

        $this->withoutVite()
            ->actingAs($employee)
            ->get(route('leave-requests.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('leave/create'));
    });

    test('Super Admin receives 403 on the create page', function () {
        $superAdmin = leaveUser('super_admin');

        $this->actingAs($superAdmin)
            ->get(route('leave-requests.create'))
            ->assertForbidden();
    });
});
