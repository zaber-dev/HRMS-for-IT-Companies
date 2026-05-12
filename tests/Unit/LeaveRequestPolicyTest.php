<?php

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Policies\LeaveRequestPolicy;
use App\Services\LeaveApprovalService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->policy = new LeaveRequestPolicy(new LeaveApprovalService);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function policyUser(string $role): User
{
    return User::factory()->create()->assignRole($role);
}

function policyRequest(User $owner, LeaveStatus $status = LeaveStatus::PendingHr): LeaveRequest
{
    return LeaveRequest::factory()->create([
        'user_id' => $owner->id,
        'status' => $status,
    ]);
}

// ---------------------------------------------------------------------------
// viewAny — Requirement 12.1
// ---------------------------------------------------------------------------

describe('viewAny()', function () {
    it('returns true for every role', function (string $role) {
        $user = policyUser($role);

        expect($this->policy->viewAny($user))->toBeTrue();
    })->with(['employee', 'hr', 'admin', 'super_admin']);
});

// ---------------------------------------------------------------------------
// create — Requirement 12.2
// ---------------------------------------------------------------------------

describe('create()', function () {
    it('returns true for employee, hr, and admin', function (string $role) {
        $user = policyUser($role);

        expect($this->policy->create($user))->toBeTrue();
    })->with(['employee', 'hr', 'admin']);

    it('returns false for super_admin', function () {
        $superAdmin = policyUser('super_admin');

        expect($this->policy->create($superAdmin))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// view — Requirement 12.6
// ---------------------------------------------------------------------------

describe('view()', function () {
    it('returns true when the user is the owner of the request', function (string $role) {
        $owner = policyUser($role);
        $request = policyRequest($owner);

        expect($this->policy->view($owner, $request))->toBeTrue();
    })->with(['employee', 'hr', 'admin']);

    it('returns true for admin viewing another user\'s request', function () {
        $admin = policyUser('admin');
        $employee = policyUser('employee');
        $request = policyRequest($employee);

        expect($this->policy->view($admin, $request))->toBeTrue();
    });

    it('returns true for super_admin viewing another user\'s request', function () {
        $superAdmin = policyUser('super_admin');
        $employee = policyUser('employee');
        $request = policyRequest($employee);

        expect($this->policy->view($superAdmin, $request))->toBeTrue();
    });

    it('returns false for employee viewing another user\'s request', function () {
        $viewer = policyUser('employee');
        $owner = policyUser('employee');
        $request = policyRequest($owner);

        expect($this->policy->view($viewer, $request))->toBeFalse();
    });

    it('returns false for hr viewing another user\'s request', function () {
        $viewer = policyUser('hr');
        $owner = policyUser('employee');
        $request = policyRequest($owner);

        expect($this->policy->view($viewer, $request))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// cancel — Requirements 9.1, 9.4
// ---------------------------------------------------------------------------

describe('cancel()', function () {
    it('returns true for the owner when status is non-terminal', function (LeaveStatus $status) {
        $owner = policyUser('employee');
        $request = policyRequest($owner, $status);

        expect($this->policy->cancel($owner, $request))->toBeTrue();
    })->with([LeaveStatus::PendingHr, LeaveStatus::PendingAdmin, LeaveStatus::PendingSuperAdmin]);

    it('returns false for the owner when status is terminal', function (LeaveStatus $status) {
        $owner = policyUser('employee');
        $request = policyRequest($owner, $status);

        expect($this->policy->cancel($owner, $request))->toBeFalse();
    })->with([LeaveStatus::Approved, LeaveStatus::Rejected, LeaveStatus::Cancelled]);

    it('returns false for a non-owner regardless of status', function (LeaveStatus $status) {
        $owner = policyUser('employee');
        $nonOwner = policyUser('employee');
        $request = policyRequest($owner, $status);

        expect($this->policy->cancel($nonOwner, $request))->toBeFalse();
    })->with([LeaveStatus::PendingHr, LeaveStatus::PendingAdmin, LeaveStatus::Approved, LeaveStatus::Rejected, LeaveStatus::Cancelled]);

    it('returns false for admin who is not the owner', function () {
        $owner = policyUser('employee');
        $admin = policyUser('admin');
        $request = policyRequest($owner, LeaveStatus::PendingHr);

        expect($this->policy->cancel($admin, $request))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// approve — Requirements 12.3–12.5, 12.8, 12.9 (delegates to LeaveApprovalService)
// ---------------------------------------------------------------------------

describe('approve()', function () {
    it('returns true when HR approves an Employee pending_hr request', function () {
        $hr = policyUser('hr');
        $employee = policyUser('employee');
        $request = policyRequest($employee, LeaveStatus::PendingHr);

        expect($this->policy->approve($hr, $request))->toBeTrue();
    });

    it('returns false when HR tries to approve a pending_admin request', function () {
        $hr = policyUser('hr');
        $employee = policyUser('employee');
        $request = policyRequest($employee, LeaveStatus::PendingAdmin);

        expect($this->policy->approve($hr, $request))->toBeFalse();
    });

    it('returns true when Admin approves an Employee pending_admin request', function () {
        $admin = policyUser('admin');
        $employee = policyUser('employee');
        $request = policyRequest($employee, LeaveStatus::PendingAdmin);

        expect($this->policy->approve($admin, $request))->toBeTrue();
    });

    it('returns false when a user tries to approve their own request', function () {
        $employee = policyUser('employee');
        $request = policyRequest($employee, LeaveStatus::PendingHr);

        expect($this->policy->approve($employee, $request))->toBeFalse();
    });

    it('returns false for any approver on a terminal request', function (string $role) {
        $approver = policyUser($role);
        $employee = policyUser('employee');
        $request = policyRequest($employee, LeaveStatus::Approved);

        expect($this->policy->approve($approver, $request))->toBeFalse();
    })->with(['hr', 'admin', 'super_admin']);

    it('returns true when SuperAdmin approves a pending_super_admin request', function () {
        $superAdmin = policyUser('super_admin');
        $hr = policyUser('hr');
        $request = policyRequest($hr, LeaveStatus::PendingSuperAdmin);

        expect($this->policy->approve($superAdmin, $request))->toBeTrue();
    });

    it('returns true when SuperAdmin approves a pending_admin request', function () {
        $superAdmin = policyUser('super_admin');
        $hr = policyUser('hr');
        $request = policyRequest($hr, LeaveStatus::PendingAdmin);

        expect($this->policy->approve($superAdmin, $request))->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// reject — Requirements 12.3–12.5, 12.8, 12.9 (delegates to LeaveApprovalService)
// ---------------------------------------------------------------------------

describe('reject()', function () {
    it('returns true when HR rejects an Employee pending_hr request', function () {
        $hr = policyUser('hr');
        $employee = policyUser('employee');
        $request = policyRequest($employee, LeaveStatus::PendingHr);

        expect($this->policy->reject($hr, $request))->toBeTrue();
    });

    it('returns false when HR tries to reject a pending_admin request', function () {
        $hr = policyUser('hr');
        $employee = policyUser('employee');
        $request = policyRequest($employee, LeaveStatus::PendingAdmin);

        expect($this->policy->reject($hr, $request))->toBeFalse();
    });

    it('returns true when Admin rejects an Employee pending_admin request', function () {
        $admin = policyUser('admin');
        $employee = policyUser('employee');
        $request = policyRequest($employee, LeaveStatus::PendingAdmin);

        expect($this->policy->reject($admin, $request))->toBeTrue();
    });

    it('returns false when a user tries to reject their own request', function () {
        $employee = policyUser('employee');
        $request = policyRequest($employee, LeaveStatus::PendingHr);

        expect($this->policy->reject($employee, $request))->toBeFalse();
    });

    it('returns false for any approver on a terminal request', function (string $role) {
        $approver = policyUser($role);
        $employee = policyUser('employee');
        $request = policyRequest($employee, LeaveStatus::Rejected);

        expect($this->policy->reject($approver, $request))->toBeFalse();
    })->with(['hr', 'admin', 'super_admin']);
});

// ---------------------------------------------------------------------------
// viewAll — Requirement 10.6
// ---------------------------------------------------------------------------

describe('viewAll()', function () {
    it('returns true for admin and super_admin', function (string $role) {
        $user = policyUser($role);

        expect($this->policy->viewAll($user))->toBeTrue();
    })->with(['admin', 'super_admin']);

    it('returns false for hr and employee', function (string $role) {
        $user = policyUser($role);

        expect($this->policy->viewAll($user))->toBeFalse();
    })->with(['hr', 'employee']);
});

describe('viewOthers()', function () {
    it('returns true for hr, admin, and super_admin', function (string $role) {
        $user = policyUser($role);

        expect($this->policy->viewOthers($user))->toBeTrue();
    })->with(['hr', 'admin', 'super_admin']);

    it('returns false for employee', function () {
        $employee = policyUser('employee');

        expect($this->policy->viewOthers($employee))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// viewApprovalQueue — Requirements 10.3–10.5
// ---------------------------------------------------------------------------

describe('viewApprovalQueue()', function () {
    it('returns true for hr, admin, and super_admin', function (string $role) {
        $user = policyUser($role);

        expect($this->policy->viewApprovalQueue($user))->toBeTrue();
    })->with(['hr', 'admin', 'super_admin']);

    it('returns false for employee', function () {
        $employee = policyUser('employee');

        expect($this->policy->viewApprovalQueue($employee))->toBeFalse();
    });
});
