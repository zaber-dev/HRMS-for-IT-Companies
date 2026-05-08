<?php

use App\Enums\ApprovalDecision;
use App\Enums\LeaveStatus;
use App\Models\ApprovalAction;
use App\Models\LeaveRequest;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create an active user with the given role.
 */
function approvalUser(string $role): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole($role);
}

/**
 * Create a leave request for the given user with the given status.
 */
function approvalRequest(User $submitter, LeaveStatus $status): LeaveRequest
{
    return LeaveRequest::factory()->create([
        'user_id' => $submitter->id,
        'status' => $status,
    ]);
}

// ---------------------------------------------------------------------------
// index — approval queue
// ---------------------------------------------------------------------------

describe('route mapping', function () {
    test('approve and reject routes use dedicated controller methods', function () {
        $approveShow = Route::getRoutes()->getByName('leave-requests.approvals.approve.show');
        $approvePost = Route::getRoutes()->getByName('leave-requests.approvals.approve');
        $rejectShow = Route::getRoutes()->getByName('leave-requests.approvals.reject.show');
        $rejectPost = Route::getRoutes()->getByName('leave-requests.approvals.reject');

        expect($approveShow)->not->toBeNull();
        expect($approvePost)->not->toBeNull();
        expect($rejectShow)->not->toBeNull();
        expect($rejectPost)->not->toBeNull();

        expect($approveShow->getActionMethod())->toBe('showApprove');
        expect($approvePost->getActionMethod())->toBe('approve');
        expect($rejectShow->getActionMethod())->toBe('showReject');
        expect($rejectPost->getActionMethod())->toBe('reject');
    });
});

describe('index (approval queue)', function () {
    test('HR can access the approval queue', function () {
        $hr = approvalUser('hr');

        $this->withoutVite()
            ->actingAs($hr)
            ->get(route('leave-requests.approvals.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('leave/approvals/index'));
    });

    test('Admin can access the approval queue', function () {
        $admin = approvalUser('admin');

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('leave-requests.approvals.index'))
            ->assertOk();
    });

    test('Super Admin can access the approval queue', function () {
        $superAdmin = approvalUser('super_admin');

        $this->withoutVite()
            ->actingAs($superAdmin)
            ->get(route('leave-requests.approvals.index'))
            ->assertOk();
    });

    test('Employee receives 403 when accessing the approval queue', function () {
        $employee = approvalUser('employee');

        $this->actingAs($employee)
            ->get(route('leave-requests.approvals.index'))
            ->assertForbidden();
    });

    test('HR queue only contains Employee pending_hr requests', function () {
        $hr = approvalUser('hr');
        $employee = approvalUser('employee');
        $otherHr = approvalUser('hr');

        // Should appear in queue
        $pendingHrRequest = approvalRequest($employee, LeaveStatus::PendingHr);

        // Should NOT appear in queue
        approvalRequest($employee, LeaveStatus::PendingAdmin);
        approvalRequest($otherHr, LeaveStatus::PendingAdmin);

        $this->withoutVite()
            ->actingAs($hr)
            ->get(route('leave-requests.approvals.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('leave/approvals/index')
                ->where('leaveRequests.total', 1)
                ->where('leaveRequests.data.0.id', $pendingHrRequest->id)
            );
    });

    test('Admin queue excludes Admin_Self_Requests', function () {
        $admin = approvalUser('admin');
        $adminSubmitter = approvalUser('admin');
        $employee = approvalUser('employee');

        // Should appear
        $employeeRequest = approvalRequest($employee, LeaveStatus::PendingAdmin);

        // Should NOT appear (Admin_Self_Request)
        approvalRequest($adminSubmitter, LeaveStatus::PendingSuperAdmin);

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('leave-requests.approvals.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('leaveRequests.total', 1)
                ->where('leaveRequests.data.0.id', $employeeRequest->id)
            );
    });
});

// ---------------------------------------------------------------------------
// approve — GET (confirmation page)
// ---------------------------------------------------------------------------

describe('approve GET (confirmation page)', function () {
    test('HR can view the approve confirmation page for an Employee pending_hr request', function () {
        $hr = approvalUser('hr');
        $employee = approvalUser('employee');
        $request = approvalRequest($employee, LeaveStatus::PendingHr);

        $this->withoutVite()
            ->actingAs($hr)
            ->get(route('leave-requests.approvals.approve.show', $request))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('leave/approvals/approve')
                ->where('leaveRequest.id', $request->id)
            );
    });

    test('Employee receives 403 when accessing the approve confirmation page', function () {
        $employee = approvalUser('employee');
        $otherEmployee = approvalUser('employee');
        $request = approvalRequest($otherEmployee, LeaveStatus::PendingHr);

        $this->actingAs($employee)
            ->get(route('leave-requests.approvals.approve.show', $request))
            ->assertForbidden();
    });
});

// ---------------------------------------------------------------------------
// approve — POST (happy paths)
// ---------------------------------------------------------------------------

describe('approve POST (happy paths)', function () {
    test('HR approves Employee pending_hr request → status becomes pending_admin', function () {
        $hr = approvalUser('hr');
        $employee = approvalUser('employee');
        $request = approvalRequest($employee, LeaveStatus::PendingHr);

        $this->actingAs($hr)
            ->post(route('leave-requests.approvals.approve', $request))
            ->assertRedirect(route('leave-requests.approvals.index'));

        expect($request->fresh()->status)->toBe(LeaveStatus::PendingAdmin);
    });

    test('Admin approves Employee pending_admin request → status becomes approved', function () {
        $admin = approvalUser('admin');
        $employee = approvalUser('employee');
        $request = approvalRequest($employee, LeaveStatus::PendingAdmin);

        $this->actingAs($admin)
            ->post(route('leave-requests.approvals.approve', $request))
            ->assertRedirect(route('leave-requests.approvals.index'));

        expect($request->fresh()->status)->toBe(LeaveStatus::Approved);
    });

    test('Admin approves HR pending_admin request → status becomes pending_super_admin', function () {
        $admin = approvalUser('admin');
        $hr = approvalUser('hr');
        $request = approvalRequest($hr, LeaveStatus::PendingAdmin);

        $this->actingAs($admin)
            ->post(route('leave-requests.approvals.approve', $request))
            ->assertRedirect(route('leave-requests.approvals.index'));

        expect($request->fresh()->status)->toBe(LeaveStatus::PendingSuperAdmin);
    });

    test('Super Admin approves pending_super_admin request → status becomes approved', function () {
        $superAdmin = approvalUser('super_admin');
        $hr = approvalUser('hr');
        $request = approvalRequest($hr, LeaveStatus::PendingSuperAdmin);

        $this->actingAs($superAdmin)
            ->post(route('leave-requests.approvals.approve', $request))
            ->assertRedirect(route('leave-requests.approvals.index'));

        expect($request->fresh()->status)->toBe(LeaveStatus::Approved);
    });

    test('Super Admin approves HR pending_admin request → status becomes approved', function () {
        $superAdmin = approvalUser('super_admin');
        $hr = approvalUser('hr');
        $request = approvalRequest($hr, LeaveStatus::PendingAdmin);

        $this->actingAs($superAdmin)
            ->post(route('leave-requests.approvals.approve', $request))
            ->assertRedirect(route('leave-requests.approvals.index'));

        expect($request->fresh()->status)->toBe(LeaveStatus::Approved);
    });

    test('Admin bypass: approves Employee pending_hr request → status becomes approved with is_bypass=true', function () {
        $admin = approvalUser('admin');
        $employee = approvalUser('employee');
        $request = approvalRequest($employee, LeaveStatus::PendingHr);

        $this->actingAs($admin)
            ->post(route('leave-requests.approvals.approve', $request))
            ->assertRedirect(route('leave-requests.approvals.index'));

        expect($request->fresh()->status)->toBe(LeaveStatus::Approved);

        $action = ApprovalAction::where('leave_request_id', $request->id)->first();
        expect($action->is_bypass)->toBeTrue();
        expect($action->decision)->toBe(ApprovalDecision::Approved);
    });

    test('approve stores an optional comment in the ApprovalAction', function () {
        $hr = approvalUser('hr');
        $employee = approvalUser('employee');
        $request = approvalRequest($employee, LeaveStatus::PendingHr);

        $this->actingAs($hr)
            ->post(route('leave-requests.approvals.approve', $request), [
                'comment' => 'Approved with notes',
            ])
            ->assertRedirect(route('leave-requests.approvals.index'));

        $action = ApprovalAction::where('leave_request_id', $request->id)->first();
        expect($action->comment)->toBe('Approved with notes');
    });
});

// ---------------------------------------------------------------------------
// approve — POST (error cases)
// ---------------------------------------------------------------------------

describe('approve POST (error cases)', function () {
    test('403 when HR attempts to approve an HR self-request', function () {
        $hr = approvalUser('hr');
        $hrSubmitter = approvalUser('hr');
        $request = approvalRequest($hrSubmitter, LeaveStatus::PendingAdmin);

        $this->actingAs($hr)
            ->post(route('leave-requests.approvals.approve', $request))
            ->assertForbidden();
    });

    test('403 when Admin attempts to approve an Admin_Self_Request', function () {
        $admin = approvalUser('admin');
        $adminSubmitter = approvalUser('admin');
        $request = approvalRequest($adminSubmitter, LeaveStatus::PendingSuperAdmin);

        $this->actingAs($admin)
            ->post(route('leave-requests.approvals.approve', $request))
            ->assertForbidden();
    });

    test('403 when user attempts to approve their own request', function () {
        $employee = approvalUser('employee');
        $request = approvalRequest($employee, LeaveStatus::PendingHr);

        $this->actingAs($employee)
            ->post(route('leave-requests.approvals.approve', $request))
            ->assertForbidden();
    });

    test('422 when attempting to approve a terminal-status request', function (LeaveStatus $status) {
        $admin = approvalUser('admin');
        $employee = approvalUser('employee');
        $request = approvalRequest($employee, $status);

        $this->actingAs($admin)
            ->post(route('leave-requests.approvals.approve', $request))
            ->assertStatus(422);
    })->with([LeaveStatus::Approved, LeaveStatus::Rejected, LeaveStatus::Cancelled]);
});

// ---------------------------------------------------------------------------
// reject — GET (form page)
// ---------------------------------------------------------------------------

describe('reject GET (form page)', function () {
    test('HR can view the reject form page for an Employee pending_hr request', function () {
        $hr = approvalUser('hr');
        $employee = approvalUser('employee');
        $request = approvalRequest($employee, LeaveStatus::PendingHr);

        $this->withoutVite()
            ->actingAs($hr)
            ->get(route('leave-requests.approvals.reject.show', $request))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('leave/approvals/reject')
                ->where('leaveRequest.id', $request->id)
            );
    });

    test('Employee receives 403 when accessing the reject form page', function () {
        $employee = approvalUser('employee');
        $otherEmployee = approvalUser('employee');
        $request = approvalRequest($otherEmployee, LeaveStatus::PendingHr);

        $this->actingAs($employee)
            ->get(route('leave-requests.approvals.reject.show', $request))
            ->assertForbidden();
    });
});

// ---------------------------------------------------------------------------
// reject — POST (happy paths)
// ---------------------------------------------------------------------------

describe('reject POST (happy paths)', function () {
    test('HR rejects Employee pending_hr request with reason → status becomes rejected', function () {
        $hr = approvalUser('hr');
        $employee = approvalUser('employee');
        $request = approvalRequest($employee, LeaveStatus::PendingHr);

        $this->actingAs($hr)
            ->post(route('leave-requests.approvals.reject', $request), [
                'reason' => 'Insufficient notice period',
            ])
            ->assertRedirect(route('leave-requests.approvals.index'));

        expect($request->fresh()->status)->toBe(LeaveStatus::Rejected);

        $action = ApprovalAction::where('leave_request_id', $request->id)->first();
        expect($action->decision)->toBe(ApprovalDecision::Rejected);
        expect($action->comment)->toBe('Insufficient notice period');
    });

    test('Admin rejects Employee pending_admin request → status becomes rejected', function () {
        $admin = approvalUser('admin');
        $employee = approvalUser('employee');
        $request = approvalRequest($employee, LeaveStatus::PendingAdmin);

        $this->actingAs($admin)
            ->post(route('leave-requests.approvals.reject', $request), [
                'reason' => 'Not approved',
            ])
            ->assertRedirect(route('leave-requests.approvals.index'));

        expect($request->fresh()->status)->toBe(LeaveStatus::Rejected);
    });

    test('Super Admin rejects pending_super_admin request → status becomes rejected', function () {
        $superAdmin = approvalUser('super_admin');
        $admin = approvalUser('admin');
        $request = approvalRequest($admin, LeaveStatus::PendingSuperAdmin);

        $this->actingAs($superAdmin)
            ->post(route('leave-requests.approvals.reject', $request), [
                'reason' => 'Denied by Super Admin',
            ])
            ->assertRedirect(route('leave-requests.approvals.index'));

        expect($request->fresh()->status)->toBe(LeaveStatus::Rejected);
    });
});

// ---------------------------------------------------------------------------
// reject — POST (error cases)
// ---------------------------------------------------------------------------

describe('reject POST (error cases)', function () {
    test('422 when reason is missing', function () {
        $hr = approvalUser('hr');
        $employee = approvalUser('employee');
        $request = approvalRequest($employee, LeaveStatus::PendingHr);

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('leave-requests.approvals.reject', $request), [])
            ->assertStatus(422)
            ->assertInvalid(['reason']);
    });

    test('403 when HR attempts to reject an HR self-request', function () {
        $hr = approvalUser('hr');
        $hrSubmitter = approvalUser('hr');
        $request = approvalRequest($hrSubmitter, LeaveStatus::PendingAdmin);

        $this->actingAs($hr)
            ->post(route('leave-requests.approvals.reject', $request), [
                'reason' => 'Denied',
            ])
            ->assertForbidden();
    });

    test('403 when user attempts to reject their own request', function () {
        $employee = approvalUser('employee');
        $request = approvalRequest($employee, LeaveStatus::PendingHr);

        $this->actingAs($employee)
            ->post(route('leave-requests.approvals.reject', $request), [
                'reason' => 'Self-reject',
            ])
            ->assertForbidden();
    });

    test('422 when attempting to reject a terminal-status request', function (LeaveStatus $status) {
        $admin = approvalUser('admin');
        $employee = approvalUser('employee');
        $request = approvalRequest($employee, $status);

        $this->actingAs($admin)
            ->post(route('leave-requests.approvals.reject', $request), [
                'reason' => 'Too late',
            ])
            ->assertStatus(422);
    })->with([LeaveStatus::Approved, LeaveStatus::Rejected, LeaveStatus::Cancelled]);
});
