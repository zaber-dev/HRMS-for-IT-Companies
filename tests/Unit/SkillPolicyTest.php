<?php

use App\Enums\AssignmentSource;
use App\Models\Skill;
use App\Models\SkillAssignment;
use App\Models\User;
use App\Policies\SkillAssignmentPolicy;
use App\Policies\SkillPolicy;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->skillPolicy = new SkillPolicy;
    $this->assignmentPolicy = new SkillAssignmentPolicy;
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create a user with the given role.
 */
function skillPolicyUser(string $role): User
{
    return User::factory()->create()->assignRole($role);
}

/**
 * Create a user with no role assigned.
 */
function skillPolicyUserNoRole(): User
{
    return User::factory()->create();
}

// ---------------------------------------------------------------------------
// SkillPolicy::viewAny — Requirements 8.1, 8.2
// ---------------------------------------------------------------------------

describe('SkillPolicy::viewAny()', function () {
    it('returns true for all four defined roles', function (string $role) {
        $user = skillPolicyUser($role);
        $skill = Skill::factory()->create();

        expect($this->skillPolicy->viewAny($user))->toBeTrue();
    })->with(['super_admin', 'admin', 'hr', 'employee']);

    it('returns false for a user with no role', function () {
        $user = skillPolicyUserNoRole();

        expect($this->skillPolicy->viewAny($user))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// SkillPolicy::view — Requirements 8.1, 8.2
// ---------------------------------------------------------------------------

describe('SkillPolicy::view()', function () {
    it('returns true for all four defined roles', function (string $role) {
        $user = skillPolicyUser($role);
        $skill = Skill::factory()->create();

        expect($this->skillPolicy->view($user, $skill))->toBeTrue();
    })->with(['super_admin', 'admin', 'hr', 'employee']);

    it('returns false for a user with no role', function () {
        $user = skillPolicyUserNoRole();
        $skill = Skill::factory()->create();

        expect($this->skillPolicy->view($user, $skill))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// SkillPolicy::create — Requirements 1.6, 8.1
// ---------------------------------------------------------------------------

describe('SkillPolicy::create()', function () {
    it('returns true for privileged roles', function (string $role) {
        $user = skillPolicyUser($role);

        expect($this->skillPolicy->create($user))->toBeTrue();
    })->with(['super_admin', 'admin', 'hr']);

    it('returns false for employee', function () {
        $user = skillPolicyUser('employee');

        expect($this->skillPolicy->create($user))->toBeFalse();
    });

    it('returns false for a user with no role', function () {
        $user = skillPolicyUserNoRole();

        expect($this->skillPolicy->create($user))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// SkillPolicy::update — Requirements 1.6, 8.1
// ---------------------------------------------------------------------------

describe('SkillPolicy::update()', function () {
    it('returns true for privileged roles', function (string $role) {
        $user = skillPolicyUser($role);
        $skill = Skill::factory()->create();

        expect($this->skillPolicy->update($user, $skill))->toBeTrue();
    })->with(['super_admin', 'admin', 'hr']);

    it('returns false for employee', function () {
        $user = skillPolicyUser('employee');
        $skill = Skill::factory()->create();

        expect($this->skillPolicy->update($user, $skill))->toBeFalse();
    });

    it('returns false for a user with no role', function () {
        $user = skillPolicyUserNoRole();
        $skill = Skill::factory()->create();

        expect($this->skillPolicy->update($user, $skill))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// SkillPolicy::delete — Requirements 2.3, 8.1
// ---------------------------------------------------------------------------

describe('SkillPolicy::delete()', function () {
    it('returns true for privileged roles', function (string $role) {
        $user = skillPolicyUser($role);
        $skill = Skill::factory()->create();

        expect($this->skillPolicy->delete($user, $skill))->toBeTrue();
    })->with(['super_admin', 'admin', 'hr']);

    it('returns false for employee', function () {
        $user = skillPolicyUser('employee');
        $skill = Skill::factory()->create();

        expect($this->skillPolicy->delete($user, $skill))->toBeFalse();
    });

    it('returns false for a user with no role', function () {
        $user = skillPolicyUserNoRole();
        $skill = Skill::factory()->create();

        expect($this->skillPolicy->delete($user, $skill))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// SkillPolicy::toggle — Requirements 8.1, 9.1
// ---------------------------------------------------------------------------

describe('SkillPolicy::toggle()', function () {
    it('returns true for privileged roles', function (string $role) {
        $user = skillPolicyUser($role);
        $skill = Skill::factory()->create();

        expect($this->skillPolicy->toggle($user, $skill))->toBeTrue();
    })->with(['super_admin', 'admin', 'hr']);

    it('returns false for employee', function () {
        $user = skillPolicyUser('employee');
        $skill = Skill::factory()->create();

        expect($this->skillPolicy->toggle($user, $skill))->toBeFalse();
    });

    it('returns false for a user with no role', function () {
        $user = skillPolicyUserNoRole();
        $skill = Skill::factory()->create();

        expect($this->skillPolicy->toggle($user, $skill))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// SkillAssignmentPolicy::create — Requirements 5.2, 8.3, 8.4
// ---------------------------------------------------------------------------

describe('SkillAssignmentPolicy::create()', function () {
    it('returns true when actor is the target (self-assign)', function (string $role) {
        $user = skillPolicyUser($role);

        expect($this->assignmentPolicy->create($user, $user))->toBeTrue();
    })->with(['employee', 'hr', 'admin', 'super_admin']);

    it('returns true when actor has a privileged role assigning to another user', function (string $role) {
        $actor = skillPolicyUser($role);
        $target = skillPolicyUser('employee');

        expect($this->assignmentPolicy->create($actor, $target))->toBeTrue();
    })->with(['hr', 'admin', 'super_admin']);

    it('returns false when employee tries to assign to a different user', function () {
        $actor = skillPolicyUser('employee');
        $target = skillPolicyUser('employee');

        expect($this->assignmentPolicy->create($actor, $target))->toBeFalse();
    });

    it('returns true when roleless user tries to assign to themselves (self-assign check passes)', function () {
        // The policy only checks actor->id === target->id OR privileged role.
        // Route-level middleware (auth + role) prevents roleless users from reaching the controller.
        $user = skillPolicyUserNoRole();

        expect($this->assignmentPolicy->create($user, $user))->toBeTrue();
    });

    it('returns false when roleless user tries to assign to another user', function () {
        $actor = skillPolicyUserNoRole();
        $target = skillPolicyUser('employee');

        expect($this->assignmentPolicy->create($actor, $target))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// SkillAssignmentPolicy::delete — Requirements 6.6, 7.5, 8.3, 8.4
// ---------------------------------------------------------------------------

describe('SkillAssignmentPolicy::delete()', function () {
    it('returns true when actor is the target and assignment source is self', function () {
        $user = skillPolicyUser('employee');
        $assignment = SkillAssignment::factory()->create([
            'user_id' => $user->id,
            'source' => AssignmentSource::Self,
        ]);

        expect($this->assignmentPolicy->delete($user, $user, $assignment))->toBeTrue();
    });

    it('returns false when actor is the target but assignment source is privileged', function () {
        $user = skillPolicyUser('employee');
        $assignment = SkillAssignment::factory()->create([
            'user_id' => $user->id,
            'source' => AssignmentSource::Privileged,
        ]);

        expect($this->assignmentPolicy->delete($user, $user, $assignment))->toBeFalse();
    });

    it('returns true when actor has a privileged role regardless of source', function (string $role) {
        $actor = skillPolicyUser($role);
        $target = skillPolicyUser('employee');
        $assignment = SkillAssignment::factory()->create([
            'user_id' => $target->id,
            'source' => AssignmentSource::Privileged,
        ]);

        expect($this->assignmentPolicy->delete($actor, $target, $assignment))->toBeTrue();
    })->with(['hr', 'admin', 'super_admin']);

    it('returns true when privileged actor removes a self-assigned skill from another user', function (string $role) {
        $actor = skillPolicyUser($role);
        $target = skillPolicyUser('employee');
        $assignment = SkillAssignment::factory()->create([
            'user_id' => $target->id,
            'source' => AssignmentSource::Self,
        ]);

        expect($this->assignmentPolicy->delete($actor, $target, $assignment))->toBeTrue();
    })->with(['hr', 'admin', 'super_admin']);

    it('returns false when employee tries to remove another user\'s self-assigned skill', function () {
        $actor = skillPolicyUser('employee');
        $target = skillPolicyUser('employee');
        $assignment = SkillAssignment::factory()->create([
            'user_id' => $target->id,
            'source' => AssignmentSource::Self,
        ]);

        expect($this->assignmentPolicy->delete($actor, $target, $assignment))->toBeFalse();
    });

    it('returns false when employee tries to remove another user\'s privileged assignment', function () {
        $actor = skillPolicyUser('employee');
        $target = skillPolicyUser('employee');
        $assignment = SkillAssignment::factory()->create([
            'user_id' => $target->id,
            'source' => AssignmentSource::Privileged,
        ]);

        expect($this->assignmentPolicy->delete($actor, $target, $assignment))->toBeFalse();
    });

    it('returns true when roleless user removes their own self-assigned skill (self-remove check passes)', function () {
        // The policy only checks (actor->id === target->id && source === Self) OR privileged role.
        // Route-level middleware (auth + role) prevents roleless users from reaching the controller.
        $user = skillPolicyUserNoRole();
        $assignment = SkillAssignment::factory()->create([
            'user_id' => $user->id,
            'source' => AssignmentSource::Self,
        ]);

        expect($this->assignmentPolicy->delete($user, $user, $assignment))->toBeTrue();
    });
});
