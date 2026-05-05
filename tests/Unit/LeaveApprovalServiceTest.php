<?php

use App\Enums\ApprovalDecision;
use App\Enums\LeaveStatus;
use App\Models\ApprovalAction;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveApprovalService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeUser(string $role): User
{
    return User::factory()->create()->assignRole($role);
}

function makeRequest(User $submitter, LeaveStatus $status): LeaveRequest
{
    return LeaveRequest::factory()->create([
        'user_id' => $submitter->id,
        'status' => $status,
    ]);
}

// ---------------------------------------------------------------------------
// initialStatus()
// ---------------------------------------------------------------------------

describe('initialStatus()', function () {
    it('returns pending_hr for an employee', function () {
        $service = new LeaveApprovalService;
        $employee = makeUser('employee');

        expect($service->initialStatus($employee))->toBe(LeaveStatus::PendingHr);
    });

    it('returns pending_admin for an hr user', function () {
        $service = new LeaveApprovalService;
        $hr = makeUser('hr');

        expect($service->initialStatus($hr))->toBe(LeaveStatus::PendingAdmin);
    });

    it('returns pending_super_admin for an admin user', function () {
        $service = new LeaveApprovalService;
        $admin = makeUser('admin');

        expect($service->initialStatus($admin))->toBe(LeaveStatus::PendingSuperAdmin);
    });
});

// ---------------------------------------------------------------------------
// canApprove() — transition table
// ---------------------------------------------------------------------------

describe('canApprove() transition table', function () {
    it('allows HR to approve an Employee pending_hr request', function () {
        $service = new LeaveApprovalService;
        $hr = makeUser('hr');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingHr);

        expect($service->canApprove($hr, $request))->toBeTrue();
    });

    it('denies HR on an Employee pending_admin request', function () {
        $service = new LeaveApprovalService;
        $hr = makeUser('hr');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingAdmin);

        expect($service->canApprove($hr, $request))->toBeFalse();
    });

    it('denies HR on an Employee pending_super_admin request', function () {
        $service = new LeaveApprovalService;
        $hr = makeUser('hr');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingSuperAdmin);

        expect($service->canApprove($hr, $request))->toBeFalse();
    });

    it('denies HR on an HR-submitted request', function () {
        $service = new LeaveApprovalService;
        $hr = makeUser('hr');
        $hrSubmitter = makeUser('hr');
        $request = makeRequest($hrSubmitter, LeaveStatus::PendingAdmin);

        expect($service->canApprove($hr, $request))->toBeFalse();
    });

    it('allows Admin to approve an Employee pending_hr request (bypass)', function () {
        $service = new LeaveApprovalService;
        $admin = makeUser('admin');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingHr);

        expect($service->canApprove($admin, $request))->toBeTrue();
    });

    it('allows Admin to approve an Employee pending_admin request', function () {
        $service = new LeaveApprovalService;
        $admin = makeUser('admin');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingAdmin);

        expect($service->canApprove($admin, $request))->toBeTrue();
    });

    it('allows Admin to approve an HR pending_admin request', function () {
        $service = new LeaveApprovalService;
        $admin = makeUser('admin');
        $hr = makeUser('hr');
        $request = makeRequest($hr, LeaveStatus::PendingAdmin);

        expect($service->canApprove($admin, $request))->toBeTrue();
    });

    it('denies Admin on an Admin-submitted request (Admin_Self_Request)', function () {
        $service = new LeaveApprovalService;
        $admin = makeUser('admin');
        $adminSubmitter = makeUser('admin');
        $request = makeRequest($adminSubmitter, LeaveStatus::PendingSuperAdmin);

        expect($service->canApprove($admin, $request))->toBeFalse();
    });

    it('denies Admin on a pending_super_admin HR request', function () {
        $service = new LeaveApprovalService;
        $admin = makeUser('admin');
        $hr = makeUser('hr');
        $request = makeRequest($hr, LeaveStatus::PendingSuperAdmin);

        expect($service->canApprove($admin, $request))->toBeFalse();
    });

    it('allows SuperAdmin to approve an Employee pending_hr request (bypass)', function () {
        $service = new LeaveApprovalService;
        $superAdmin = makeUser('super_admin');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingHr);

        expect($service->canApprove($superAdmin, $request))->toBeTrue();
    });

    it('allows SuperAdmin to approve an Employee pending_admin request (bypass)', function () {
        $service = new LeaveApprovalService;
        $superAdmin = makeUser('super_admin');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingAdmin);

        expect($service->canApprove($superAdmin, $request))->toBeTrue();
    });

    it('allows SuperAdmin to approve an HR pending_super_admin request', function () {
        $service = new LeaveApprovalService;
        $superAdmin = makeUser('super_admin');
        $hr = makeUser('hr');
        $request = makeRequest($hr, LeaveStatus::PendingSuperAdmin);

        expect($service->canApprove($superAdmin, $request))->toBeTrue();
    });

    it('allows SuperAdmin to approve an Admin pending_super_admin request', function () {
        $service = new LeaveApprovalService;
        $superAdmin = makeUser('super_admin');
        $admin = makeUser('admin');
        $request = makeRequest($admin, LeaveStatus::PendingSuperAdmin);

        expect($service->canApprove($superAdmin, $request))->toBeTrue();
    });

    it('denies SuperAdmin on an HR pending_admin request (not yet at super_admin stage)', function () {
        $service = new LeaveApprovalService;
        $superAdmin = makeUser('super_admin');
        $hr = makeUser('hr');
        $request = makeRequest($hr, LeaveStatus::PendingAdmin);

        expect($service->canApprove($superAdmin, $request))->toBeFalse();
    });

    it('denies any approver on a terminal approved request', function (string $approverRole) {
        $service = new LeaveApprovalService;
        $approver = makeUser($approverRole);
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::Approved);

        expect($service->canApprove($approver, $request))->toBeFalse();
    })->with(['hr', 'admin', 'super_admin']);

    it('denies any approver on a terminal rejected request', function (string $approverRole) {
        $service = new LeaveApprovalService;
        $approver = makeUser($approverRole);
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::Rejected);

        expect($service->canApprove($approver, $request))->toBeFalse();
    })->with(['hr', 'admin', 'super_admin']);

    it('denies any approver on a terminal cancelled request', function (string $approverRole) {
        $service = new LeaveApprovalService;
        $approver = makeUser($approverRole);
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::Cancelled);

        expect($service->canApprove($approver, $request))->toBeFalse();
    })->with(['hr', 'admin', 'super_admin']);
});

// ---------------------------------------------------------------------------
// canApprove() — self-approval prevention
// ---------------------------------------------------------------------------

describe('canApprove() self-approval prevention', function () {
    it('always returns false when the approver is the submitter', function (string $role, LeaveStatus $status) {
        $service = new LeaveApprovalService;
        $user = makeUser($role);
        $request = makeRequest($user, $status);

        expect($service->canApprove($user, $request))->toBeFalse();
    })->with([
        ['employee', LeaveStatus::PendingHr],
        ['hr', LeaveStatus::PendingAdmin],
        ['admin', LeaveStatus::PendingSuperAdmin],
    ]);
});

// ---------------------------------------------------------------------------
// isBypass()
// ---------------------------------------------------------------------------

describe('isBypass()', function () {
    it('returns true when Admin acts on an Employee pending_hr request', function () {
        $service = new LeaveApprovalService;
        $admin = makeUser('admin');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingHr);

        expect($service->isBypass($admin, $request))->toBeTrue();
    });

    it('returns true when SuperAdmin acts on an Employee pending_hr request', function () {
        $service = new LeaveApprovalService;
        $superAdmin = makeUser('super_admin');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingHr);

        expect($service->isBypass($superAdmin, $request))->toBeTrue();
    });

    it('returns true when SuperAdmin acts on an Employee pending_admin request', function () {
        $service = new LeaveApprovalService;
        $superAdmin = makeUser('super_admin');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingAdmin);

        expect($service->isBypass($superAdmin, $request))->toBeTrue();
    });

    it('returns false when Admin acts on an Employee pending_admin request (normal flow)', function () {
        $service = new LeaveApprovalService;
        $admin = makeUser('admin');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingAdmin);

        expect($service->isBypass($admin, $request))->toBeFalse();
    });

    it('returns false when HR acts on an Employee pending_hr request', function () {
        $service = new LeaveApprovalService;
        $hr = makeUser('hr');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingHr);

        expect($service->isBypass($hr, $request))->toBeFalse();
    });

    it('returns false when Admin acts on an HR request', function () {
        $service = new LeaveApprovalService;
        $admin = makeUser('admin');
        $hr = makeUser('hr');
        $request = makeRequest($hr, LeaveStatus::PendingAdmin);

        expect($service->isBypass($admin, $request))->toBeFalse();
    });

    it('returns false when SuperAdmin acts on an Admin pending_super_admin request', function () {
        $service = new LeaveApprovalService;
        $superAdmin = makeUser('super_admin');
        $admin = makeUser('admin');
        $request = makeRequest($admin, LeaveStatus::PendingSuperAdmin);

        expect($service->isBypass($superAdmin, $request))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// approve()
// ---------------------------------------------------------------------------

describe('approve()', function () {
    it('creates exactly one ApprovalAction with correct fields', function () {
        $service = new LeaveApprovalService;
        $hr = makeUser('hr');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingHr);

        $countBefore = ApprovalAction::count();

        $action = $service->approve($hr, $request, 'Looks good');

        expect(ApprovalAction::count())->toBe($countBefore + 1);
        expect($action->leave_request_id)->toBe($request->id);
        expect($action->user_id)->toBe($hr->id);
        expect($action->decision)->toBe(ApprovalDecision::Approved);
        expect($action->comment)->toBe('Looks good');
        expect($action->is_bypass)->toBeFalse();
    });

    it('creates an ApprovalAction with is_bypass=true for a bypass approval', function () {
        $service = new LeaveApprovalService;
        $admin = makeUser('admin');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingHr);

        $action = $service->approve($admin, $request);

        expect($action->is_bypass)->toBeTrue();
        expect($action->decision)->toBe(ApprovalDecision::Approved);
    });

    it('transitions status correctly after HR approves an Employee request', function () {
        $service = new LeaveApprovalService;
        $hr = makeUser('hr');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingHr);

        $service->approve($hr, $request);

        expect($request->fresh()->status)->toBe(LeaveStatus::PendingAdmin);
    });

    it('transitions status to approved when Admin approves an Employee pending_admin request', function () {
        $service = new LeaveApprovalService;
        $admin = makeUser('admin');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingAdmin);

        $service->approve($admin, $request);

        expect($request->fresh()->status)->toBe(LeaveStatus::Approved);
    });
});

// ---------------------------------------------------------------------------
// reject()
// ---------------------------------------------------------------------------

describe('reject()', function () {
    it('sets status to rejected and records reason in ApprovalAction', function () {
        $service = new LeaveApprovalService;
        $hr = makeUser('hr');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingHr);

        $action = $service->reject($hr, $request, 'Insufficient notice');

        expect($request->fresh()->status)->toBe(LeaveStatus::Rejected);
        expect($action->decision)->toBe(ApprovalDecision::Rejected);
        expect($action->comment)->toBe('Insufficient notice');
        expect($action->leave_request_id)->toBe($request->id);
        expect($action->user_id)->toBe($hr->id);
    });

    it('creates exactly one ApprovalAction on rejection', function () {
        $service = new LeaveApprovalService;
        $admin = makeUser('admin');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingAdmin);

        $countBefore = ApprovalAction::count();

        $service->reject($admin, $request, 'Not approved');

        expect(ApprovalAction::count())->toBe($countBefore + 1);
    });

    it('sets is_bypass=true when Admin rejects an Employee pending_hr request', function () {
        $service = new LeaveApprovalService;
        $admin = makeUser('admin');
        $employee = makeUser('employee');
        $request = makeRequest($employee, LeaveStatus::PendingHr);

        $action = $service->reject($admin, $request, 'Denied');

        expect($action->is_bypass)->toBeTrue();
    });
});
