<?php

use App\Enums\ApprovalDecision;
use App\Enums\LeaveStatus;
use App\Models\ApprovalAction;
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
 * Create an active user with the given role (no forced password change).
 */
function propUser(string $role): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole($role);
}

/**
 * Create a leave request for the given user with the given status.
 */
function propRequest(User $submitter, LeaveStatus $status): LeaveRequest
{
    return LeaveRequest::factory()->create([
        'user_id' => $submitter->id,
        'status' => $status,
        'start_date' => now()->addDays(10)->toDateString(),
        'end_date' => now()->addDays(15)->toDateString(),
    ]);
}

// ---------------------------------------------------------------------------
// Property 1: Initial status is determined by submitter's role
// Feature: pto-leave-management, Property 1: initial status is determined by submitter's role
// ---------------------------------------------------------------------------

it('sets initial leave request status based on submitter role', function (string $role, string $expectedStatus) {
    $user = propUser($role);

    $this->actingAs($user)
        ->post(route('leave-requests.store'), [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(rand(2, 10))->toDateString(),
            'reason' => fake()->sentence(),
        ])
        ->assertRedirect(route('leave-requests.index'));

    $request = LeaveRequest::where('user_id', $user->id)->latest()->first();

    expect($request)->not->toBeNull();
    expect($request->status->value)->toBe($expectedStatus);
})->with([
    ['employee', 'pending_hr'],
    ['hr', 'pending_admin'],
    ['admin', 'pending_super_admin'],
])->repeat(34);
// Feature: pto-leave-management, Property 1: initial status is determined by submitter's role

// ---------------------------------------------------------------------------
// Property 2: Date validation rejects invalid ranges
// Feature: pto-leave-management, Property 2: date validation rejects invalid ranges
// ---------------------------------------------------------------------------

it('rejects leave requests with a past start date', function () {
    $user = propUser('employee');
    $daysBack = rand(1, 365);

    $this->actingAs($user)
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('leave-requests.store'), [
            'start_date' => now()->subDays($daysBack)->toDateString(),
            'end_date' => now()->addDays(rand(1, 10))->toDateString(),
            'reason' => fake()->sentence(),
        ])
        ->assertStatus(422)
        ->assertInvalid(['start_date']);
})->repeat(50);
// Feature: pto-leave-management, Property 2: date validation rejects invalid ranges

it('rejects leave requests where end date is before start date', function () {
    $user = propUser('employee');
    $startOffset = rand(5, 30);
    $endOffset = rand(1, $startOffset - 1);

    $this->actingAs($user)
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('leave-requests.store'), [
            'start_date' => now()->addDays($startOffset)->toDateString(),
            'end_date' => now()->addDays($endOffset)->toDateString(),
            'reason' => fake()->sentence(),
        ])
        ->assertStatus(422)
        ->assertInvalid(['end_date']);
})->repeat(50);
// Feature: pto-leave-management, Property 2: date validation rejects invalid ranges

// ---------------------------------------------------------------------------
// Property 3: Overlap detection rejects conflicting requests
// Feature: pto-leave-management, Property 3: overlap detection rejects conflicting requests
// ---------------------------------------------------------------------------

it('rejects a new leave request that overlaps an existing non-terminal request', function (string $role) {
    $user = propUser($role);

    // Existing request: days 10–20
    LeaveRequest::factory()->create([
        'user_id' => $user->id,
        'start_date' => now()->addDays(10)->toDateString(),
        'end_date' => now()->addDays(20)->toDateString(),
        'status' => LeaveStatus::PendingHr,
    ]);

    // Overlapping patterns: partial overlap, full containment, same dates
    $overlaps = [
        [now()->addDays(15)->toDateString(), now()->addDays(25)->toDateString()], // partial right
        [now()->addDays(5)->toDateString(), now()->addDays(12)->toDateString()],  // partial left
        [now()->addDays(12)->toDateString(), now()->addDays(18)->toDateString()], // fully inside
        [now()->addDays(8)->toDateString(), now()->addDays(22)->toDateString()],  // fully contains
        [now()->addDays(10)->toDateString(), now()->addDays(20)->toDateString()], // same dates
    ];

    [$start, $end] = $overlaps[array_rand($overlaps)];

    $this->actingAs($user)
        ->post(route('leave-requests.store'), [
            'start_date' => $start,
            'end_date' => $end,
            'reason' => fake()->sentence(),
        ])
        ->assertRedirect()
        ->assertSessionHasErrors(['start_date']);
})->with([
    ['employee'],
    ['hr'],
    ['admin'],
])->repeat(34);
// Feature: pto-leave-management, Property 3: overlap detection rejects conflicting requests

// ---------------------------------------------------------------------------
// Property 4: Status transitions follow valid paths only
// Feature: pto-leave-management, Property 4: status transitions follow valid paths only
// ---------------------------------------------------------------------------

it('transitions leave request status correctly for valid approver/submitter/status combinations', function (
    string $approverRole,
    string $submitterRole,
    LeaveStatus $currentStatus,
    LeaveStatus $expectedStatus,
    bool $expectedBypass,
) {
    $approver = propUser($approverRole);
    $submitter = propUser($submitterRole);
    $request = propRequest($submitter, $currentStatus);

    $this->actingAs($approver)
        ->post(route('leave-requests.approvals.approve', $request))
        ->assertRedirect(route('leave-requests.approvals.index'));

    expect($request->fresh()->status)->toBe($expectedStatus);

    $action = ApprovalAction::where('leave_request_id', $request->id)->latest()->first();
    expect($action)->not->toBeNull();
    expect($action->is_bypass)->toBe($expectedBypass);
})->with([
    // HR | Employee | pending_hr → pending_admin | not bypass
    ['hr', 'employee', LeaveStatus::PendingHr, LeaveStatus::PendingAdmin, false],
    // Admin | Employee | pending_hr → approved | bypass
    ['admin', 'employee', LeaveStatus::PendingHr, LeaveStatus::Approved, true],
    // Admin | Employee | pending_admin → approved | not bypass
    ['admin', 'employee', LeaveStatus::PendingAdmin, LeaveStatus::Approved, false],
    // Admin | HR | pending_admin → pending_super_admin | not bypass
    ['admin', 'hr', LeaveStatus::PendingAdmin, LeaveStatus::PendingSuperAdmin, false],
    // Super Admin | Employee | pending_hr → approved | bypass
    ['super_admin', 'employee', LeaveStatus::PendingHr, LeaveStatus::Approved, true],
    // Super Admin | Employee | pending_admin → approved | bypass
    ['super_admin', 'employee', LeaveStatus::PendingAdmin, LeaveStatus::Approved, true],
    // Super Admin | HR | pending_super_admin → approved | not bypass
    ['super_admin', 'hr', LeaveStatus::PendingSuperAdmin, LeaveStatus::Approved, false],
    // Super Admin | Admin | pending_super_admin → approved | not bypass
    ['super_admin', 'admin', LeaveStatus::PendingSuperAdmin, LeaveStatus::Approved, false],
])->repeat(13);
// Feature: pto-leave-management, Property 4: status transitions follow valid paths only

it('returns 403 for invalid approver/submitter/status combinations', function (
    string $approverRole,
    string $submitterRole,
    LeaveStatus $currentStatus,
) {
    $approver = propUser($approverRole);
    $submitter = propUser($submitterRole);
    $request = propRequest($submitter, $currentStatus);

    $this->actingAs($approver)
        ->post(route('leave-requests.approvals.approve', $request))
        ->assertForbidden();
})->with([
    // HR trying to approve non-Employee (HR submitter)
    ['hr', 'hr', LeaveStatus::PendingAdmin],
    // HR trying to approve non-pending_hr Employee request
    ['hr', 'employee', LeaveStatus::PendingAdmin],
    // Admin trying to approve Admin_Self_Request
    ['admin', 'admin', LeaveStatus::PendingSuperAdmin],
])->repeat(34);
// Feature: pto-leave-management, Property 4: status transitions follow valid paths only

// ---------------------------------------------------------------------------
// Property 5: No user may approve their own leave request
// Feature: pto-leave-management, Property 5: no user may approve their own leave request
// ---------------------------------------------------------------------------

it('returns 403 when a user attempts to approve their own leave request', function (string $role, LeaveStatus $status) {
    $user = propUser($role);
    $request = propRequest($user, $status);

    $this->actingAs($user)
        ->post(route('leave-requests.approvals.approve', $request))
        ->assertForbidden();
})->with([
    ['employee', LeaveStatus::PendingHr],
    ['hr', LeaveStatus::PendingAdmin],
    ['admin', LeaveStatus::PendingSuperAdmin],
])->repeat(34);
// Feature: pto-leave-management, Property 5: no user may approve their own leave request

// ---------------------------------------------------------------------------
// Property 6: Every approval or rejection creates exactly one ApprovalAction record
// Feature: pto-leave-management, Property 6: every approval or rejection creates exactly one ApprovalAction record
// ---------------------------------------------------------------------------

it('creates exactly one ApprovalAction record for each approve action', function (
    string $approverRole,
    string $submitterRole,
    LeaveStatus $currentStatus,
    ApprovalDecision $decision,
    bool $expectedBypass,
) {
    $approver = propUser($approverRole);
    $submitter = propUser($submitterRole);
    $request = propRequest($submitter, $currentStatus);

    $countBefore = ApprovalAction::count();

    if ($decision === ApprovalDecision::Approved) {
        $this->actingAs($approver)
            ->post(route('leave-requests.approvals.approve', $request))
            ->assertRedirect();
    } else {
        $this->actingAs($approver)
            ->post(route('leave-requests.approvals.reject', $request), [
                'reason' => fake()->sentence(),
            ])
            ->assertRedirect();
    }

    expect(ApprovalAction::count())->toBe($countBefore + 1);

    $action = ApprovalAction::where('leave_request_id', $request->id)->latest()->first();
    expect($action->leave_request_id)->toBe($request->id);
    expect($action->user_id)->toBe($approver->id);
    expect($action->decision)->toBe($decision);
    expect($action->is_bypass)->toBe($expectedBypass);
})->with([
    ['hr', 'employee', LeaveStatus::PendingHr, ApprovalDecision::Approved, false],
    ['hr', 'employee', LeaveStatus::PendingHr, ApprovalDecision::Rejected, false],
    ['admin', 'employee', LeaveStatus::PendingAdmin, ApprovalDecision::Approved, false],
    ['admin', 'employee', LeaveStatus::PendingAdmin, ApprovalDecision::Rejected, false],
    ['admin', 'employee', LeaveStatus::PendingHr, ApprovalDecision::Approved, true],  // bypass
    ['admin', 'employee', LeaveStatus::PendingHr, ApprovalDecision::Rejected, true],  // bypass
    ['super_admin', 'hr', LeaveStatus::PendingSuperAdmin, ApprovalDecision::Approved, false],
    ['super_admin', 'admin', LeaveStatus::PendingSuperAdmin, ApprovalDecision::Rejected, false],
])->repeat(13);
// Feature: pto-leave-management, Property 6: every approval or rejection creates exactly one ApprovalAction record

// ---------------------------------------------------------------------------
// Property 7: Bypass approval sets is_bypass=true and resolves to terminal status
// Feature: pto-leave-management, Property 7: bypass approval sets is_bypass=true and resolves to terminal status
// ---------------------------------------------------------------------------

it('sets is_bypass=true and resolves to a terminal status for bypass approvals', function (
    string $approverRole,
    LeaveStatus $currentStatus,
    ApprovalDecision $decision,
) {
    $approver = propUser($approverRole);
    $employee = propUser('employee');
    $request = propRequest($employee, $currentStatus);

    if ($decision === ApprovalDecision::Approved) {
        $this->actingAs($approver)
            ->post(route('leave-requests.approvals.approve', $request))
            ->assertRedirect();
    } else {
        $this->actingAs($approver)
            ->post(route('leave-requests.approvals.reject', $request), [
                'reason' => fake()->sentence(),
            ])
            ->assertRedirect();
    }

    $action = ApprovalAction::where('leave_request_id', $request->id)->latest()->first();
    expect($action->is_bypass)->toBeTrue();

    $freshRequest = $request->fresh();
    expect($freshRequest->status->isTerminal())->toBeTrue();
    expect($freshRequest->status)->toBeIn([LeaveStatus::Approved, LeaveStatus::Rejected]);
})->with([
    ['admin', LeaveStatus::PendingHr, ApprovalDecision::Approved],
    ['admin', LeaveStatus::PendingHr, ApprovalDecision::Rejected],
    ['super_admin', LeaveStatus::PendingHr, ApprovalDecision::Approved],
    ['super_admin', LeaveStatus::PendingHr, ApprovalDecision::Rejected],
    ['super_admin', LeaveStatus::PendingAdmin, ApprovalDecision::Approved],
    ['super_admin', LeaveStatus::PendingAdmin, ApprovalDecision::Rejected],
])->repeat(17);
// Feature: pto-leave-management, Property 7: bypass approval sets is_bypass=true and resolves to terminal status

// ---------------------------------------------------------------------------
// Property 8: HR users can only act on Employee pending_hr requests
// Feature: pto-leave-management, Property 8: HR users can only act on Employee pending_hr requests
// ---------------------------------------------------------------------------

it('returns 403 when HR attempts to approve a non-Employee request', function (string $submitterRole, LeaveStatus $status) {
    $hr = propUser('hr');
    $submitter = propUser($submitterRole);
    $request = propRequest($submitter, $status);

    $this->actingAs($hr)
        ->post(route('leave-requests.approvals.approve', $request))
        ->assertForbidden();
})->with([
    // HR submitter — HR cannot approve HR requests
    ['hr', LeaveStatus::PendingAdmin],
    // Admin submitter — HR cannot approve Admin requests
    ['admin', LeaveStatus::PendingSuperAdmin],
])->repeat(50);
// Feature: pto-leave-management, Property 8: HR users can only act on Employee pending_hr requests

it('returns 403 when HR attempts to approve an Employee request not in pending_hr status', function (LeaveStatus $status) {
    $hr = propUser('hr');
    $employee = propUser('employee');
    $request = propRequest($employee, $status);

    $this->actingAs($hr)
        ->post(route('leave-requests.approvals.approve', $request))
        ->assertForbidden();
})->with([
    [LeaveStatus::PendingAdmin],
    [LeaveStatus::PendingSuperAdmin],
])->repeat(50);
// Feature: pto-leave-management, Property 8: HR users can only act on Employee pending_hr requests

it('returns 403 when HR attempts to reject a non-Employee or non-pending_hr request', function (string $submitterRole, LeaveStatus $status) {
    $hr = propUser('hr');
    $submitter = propUser($submitterRole);
    $request = propRequest($submitter, $status);

    $this->actingAs($hr)
        ->post(route('leave-requests.approvals.reject', $request), [
            'reason' => fake()->sentence(),
        ])
        ->assertForbidden();
})->with([
    ['hr', LeaveStatus::PendingAdmin],
    ['admin', LeaveStatus::PendingSuperAdmin],
    ['employee', LeaveStatus::PendingAdmin],
])->repeat(34);
// Feature: pto-leave-management, Property 8: HR users can only act on Employee pending_hr requests

// ---------------------------------------------------------------------------
// Property 9: Cancellation is only permitted for non-terminal requests by the owner
// Feature: pto-leave-management, Property 9: cancellation is only permitted for non-terminal requests by the owner
// ---------------------------------------------------------------------------

it('returns 403 when owner attempts to cancel a terminal-status request', function (LeaveStatus $terminalStatus) {
    $user = propUser('employee');
    $request = propRequest($user, $terminalStatus);

    $this->actingAs($user)
        ->delete(route('leave-requests.cancel', $request))
        ->assertForbidden();
})->with([
    [LeaveStatus::Approved],
    [LeaveStatus::Rejected],
    [LeaveStatus::Cancelled],
])->repeat(34);
// Feature: pto-leave-management, Property 9: cancellation is only permitted for non-terminal requests by the owner

it('returns 403 when a non-owner attempts to cancel a leave request', function (string $ownerRole, string $otherRole) {
    $owner = propUser($ownerRole);
    $other = propUser($otherRole);
    $request = propRequest($owner, LeaveStatus::PendingHr);

    $this->actingAs($other)
        ->delete(route('leave-requests.cancel', $request))
        ->assertForbidden();
})->with([
    ['employee', 'employee'],
    ['employee', 'hr'],
    ['hr', 'admin'],
])->repeat(34);
// Feature: pto-leave-management, Property 9: cancellation is only permitted for non-terminal requests by the owner

// ---------------------------------------------------------------------------
// Property 10: Personal index returns only the authenticated user's requests
// Feature: pto-leave-management, Property 10: personal index returns only the authenticated user's requests
// ---------------------------------------------------------------------------

it('returns only the authenticated user\'s leave requests on the personal index', function (string $role) {
    $user = propUser($role);

    // Create requests for this user
    $ownCount = rand(1, 3);
    for ($i = 0; $i < $ownCount; $i++) {
        LeaveRequest::factory()->create([
            'user_id' => $user->id,
            'start_date' => now()->addDays(10 + $i * 5)->toDateString(),
            'end_date' => now()->addDays(12 + $i * 5)->toDateString(),
        ]);
    }

    // Create requests for other users (should not appear)
    $otherUser = propUser($role);
    LeaveRequest::factory()->count(rand(1, 3))->create([
        'user_id' => $otherUser->id,
    ]);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('leave-requests.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('leave/index')
            ->where('leaveRequests.total', $ownCount)
        );
})->with([
    ['employee'],
    ['hr'],
    ['admin'],
])->repeat(34);
// Feature: pto-leave-management, Property 10: personal index returns only the authenticated user's requests
