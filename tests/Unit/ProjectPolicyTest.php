<?php

use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\User;
use App\Policies\ProjectAssignmentPolicy;
use App\Policies\ProjectPolicy;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->projectPolicy = new ProjectPolicy;
    $this->assignmentPolicy = new ProjectAssignmentPolicy;
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create a user with the given role.
 */
function projectPolicyUser(string $role): User
{
    return User::factory()->create()->assignRole($role);
}

// ---------------------------------------------------------------------------
// ProjectPolicy::viewAny — Requirements 12.1, 12.4
// ---------------------------------------------------------------------------

describe('ProjectPolicy::viewAny()', function () {
    it('returns true for privileged roles', function (string $role) {
        $user = projectPolicyUser($role);

        expect($this->projectPolicy->viewAny($user))->toBeTrue();
    })->with(['hr', 'admin', 'super_admin']);

    it('returns false for employee', function () {
        $user = projectPolicyUser('employee');

        expect($this->projectPolicy->viewAny($user))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// ProjectPolicy::view — Requirements 8.2, 8.4, 12.3
// ---------------------------------------------------------------------------

describe('ProjectPolicy::view()', function () {
    it('returns true for privileged roles regardless of assignment', function (string $role) {
        $user = projectPolicyUser($role);
        $project = Project::factory()->create();

        expect($this->projectPolicy->view($user, $project))->toBeTrue();
    })->with(['hr', 'admin', 'super_admin']);

    it('returns true for employee who is assigned to the project', function () {
        $employee = projectPolicyUser('employee');
        $project = Project::factory()->create();

        ProjectAssignment::factory()->create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
        ]);

        expect($this->projectPolicy->view($employee, $project))->toBeTrue();
    });

    it('returns false for employee who is not assigned to the project', function () {
        $employee = projectPolicyUser('employee');
        $project = Project::factory()->create();

        expect($this->projectPolicy->view($employee, $project))->toBeFalse();
    });

    it('returns false for employee assigned to a different project', function () {
        $employee = projectPolicyUser('employee');
        $assignedProject = Project::factory()->create();
        $otherProject = Project::factory()->create();

        ProjectAssignment::factory()->create([
            'user_id' => $employee->id,
            'project_id' => $assignedProject->id,
        ]);

        expect($this->projectPolicy->view($employee, $otherProject))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// ProjectPolicy::create — Requirements 1.7, 12.1
// ---------------------------------------------------------------------------

describe('ProjectPolicy::create()', function () {
    it('returns true for privileged roles', function (string $role) {
        $user = projectPolicyUser($role);

        expect($this->projectPolicy->create($user))->toBeTrue();
    })->with(['hr', 'admin', 'super_admin']);

    it('returns false for employee', function () {
        $user = projectPolicyUser('employee');

        expect($this->projectPolicy->create($user))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// ProjectPolicy::update — Requirements 2.4, 12.1
// ---------------------------------------------------------------------------

describe('ProjectPolicy::update()', function () {
    it('returns true for privileged roles', function (string $role) {
        $user = projectPolicyUser($role);
        $project = Project::factory()->create();

        expect($this->projectPolicy->update($user, $project))->toBeTrue();
    })->with(['hr', 'admin', 'super_admin']);

    it('returns false for employee', function () {
        $user = projectPolicyUser('employee');
        $project = Project::factory()->create();

        expect($this->projectPolicy->update($user, $project))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// ProjectPolicy::delete — Requirements 2.4, 12.1
// ---------------------------------------------------------------------------

describe('ProjectPolicy::delete()', function () {
    it('returns true for privileged roles', function (string $role) {
        $user = projectPolicyUser($role);
        $project = Project::factory()->create();

        expect($this->projectPolicy->delete($user, $project))->toBeTrue();
    })->with(['hr', 'admin', 'super_admin']);

    it('returns false for employee', function () {
        $user = projectPolicyUser('employee');
        $project = Project::factory()->create();

        expect($this->projectPolicy->delete($user, $project))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// ProjectAssignmentPolicy::create — Requirements 5.8, 12.2
// ---------------------------------------------------------------------------

describe('ProjectAssignmentPolicy::create()', function () {
    it('returns true for privileged roles', function (string $role) {
        $user = projectPolicyUser($role);
        $project = Project::factory()->create();

        expect($this->assignmentPolicy->create($user, $project))->toBeTrue();
    })->with(['hr', 'admin', 'super_admin']);

    it('returns false for employee', function () {
        $user = projectPolicyUser('employee');
        $project = Project::factory()->create();

        expect($this->assignmentPolicy->create($user, $project))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// ProjectAssignmentPolicy::update — Requirements 11.4, 12.2
// ---------------------------------------------------------------------------

describe('ProjectAssignmentPolicy::update()', function () {
    it('returns true for privileged roles', function (string $role) {
        $user = projectPolicyUser($role);
        $assignment = ProjectAssignment::factory()->create();

        expect($this->assignmentPolicy->update($user, $assignment))->toBeTrue();
    })->with(['hr', 'admin', 'super_admin']);

    it('returns false for employee', function () {
        $user = projectPolicyUser('employee');
        $assignment = ProjectAssignment::factory()->create();

        expect($this->assignmentPolicy->update($user, $assignment))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// ProjectAssignmentPolicy::delete — Requirements 10.5, 12.2
// ---------------------------------------------------------------------------

describe('ProjectAssignmentPolicy::delete()', function () {
    it('returns true for privileged roles', function (string $role) {
        $user = projectPolicyUser($role);
        $assignment = ProjectAssignment::factory()->create();

        expect($this->assignmentPolicy->delete($user, $assignment))->toBeTrue();
    })->with(['hr', 'admin', 'super_admin']);

    it('returns false for employee', function () {
        $user = projectPolicyUser('employee');
        $assignment = ProjectAssignment::factory()->create();

        expect($this->assignmentPolicy->delete($user, $assignment))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// ProjectAssignmentPolicy::markComplete — Requirements 7.4
// ---------------------------------------------------------------------------

describe('ProjectAssignmentPolicy::markComplete()', function () {
    it('returns true for the employee who owns the assignment', function () {
        $employee = projectPolicyUser('employee');
        $assignment = ProjectAssignment::factory()->create(['user_id' => $employee->id]);

        expect($this->assignmentPolicy->markComplete($employee, $assignment))->toBeTrue();
    });

    it('returns false for an employee who does not own the assignment', function () {
        $owningEmployee = projectPolicyUser('employee');
        $otherEmployee = projectPolicyUser('employee');
        $assignment = ProjectAssignment::factory()->create(['user_id' => $owningEmployee->id]);

        expect($this->assignmentPolicy->markComplete($otherEmployee, $assignment))->toBeFalse();
    });

    it('returns false for privileged roles even if they own the assignment record', function (string $role) {
        $user = projectPolicyUser($role);
        $assignment = ProjectAssignment::factory()->create(['user_id' => $user->id]);

        expect($this->assignmentPolicy->markComplete($user, $assignment))->toBeFalse();
    })->with(['hr', 'admin', 'super_admin']);

    it('returns false for privileged roles on any assignment', function (string $role) {
        $user = projectPolicyUser($role);
        $employee = projectPolicyUser('employee');
        $assignment = ProjectAssignment::factory()->create(['user_id' => $employee->id]);

        expect($this->assignmentPolicy->markComplete($user, $assignment))->toBeFalse();
    })->with(['hr', 'admin', 'super_admin']);
});
