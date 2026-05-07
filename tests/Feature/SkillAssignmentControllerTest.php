<?php

use App\Enums\AssignmentSource;
use App\Models\Skill;
use App\Models\SkillAssignment;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Happy paths — store
// ---------------------------------------------------------------------------

describe('store (happy path)', function () {
    test('Employee self-assigns an active skill; assignment created with source=self', function () {
        $employee = skillUser('employee');
        $skill = Skill::factory()->create(['is_active' => true]);

        $this->actingAs($employee)
            ->post(route('users.skills.store', $employee), ['skill_id' => $skill->id])
            ->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('skill_assignments', [
            'user_id' => $employee->id,
            'skill_id' => $skill->id,
            'source' => AssignmentSource::Self->value,
        ]);
    });

    test('HR assigns skill to employee; assignment created with source=privileged', function () {
        $hr = skillUser('hr');
        $employee = skillUser('employee');
        $skill = Skill::factory()->create(['is_active' => true]);

        $this->actingAs($hr)
            ->post(route('users.skills.store', $employee), ['skill_id' => $skill->id])
            ->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('skill_assignments', [
            'user_id' => $employee->id,
            'skill_id' => $skill->id,
            'source' => AssignmentSource::Privileged->value,
        ]);
    });
});

// ---------------------------------------------------------------------------
// Happy paths — destroy
// ---------------------------------------------------------------------------

describe('destroy (happy path)', function () {
    test('Employee removes their own self-assigned skill; assignment deleted', function () {
        $employee = skillUser('employee');
        $skill = Skill::factory()->create();
        SkillAssignment::factory()->create([
            'user_id' => $employee->id,
            'skill_id' => $skill->id,
            'source' => 'self',
        ]);

        $this->actingAs($employee)
            ->delete(route('users.skills.destroy', [$employee, $skill]))
            ->assertRedirect(route('profile.edit'));

        $this->assertDatabaseMissing('skill_assignments', [
            'user_id' => $employee->id,
            'skill_id' => $skill->id,
        ]);
    });

    test('HR removes any assignment from employee; assignment deleted', function () {
        $hr = skillUser('hr');
        $employee = skillUser('employee');
        $skill = Skill::factory()->create();
        SkillAssignment::factory()->privileged()->create([
            'user_id' => $employee->id,
            'skill_id' => $skill->id,
        ]);

        $this->actingAs($hr)
            ->delete(route('users.skills.destroy', [$employee, $skill]))
            ->assertRedirect(route('profile.edit'));

        $this->assertDatabaseMissing('skill_assignments', [
            'user_id' => $employee->id,
            'skill_id' => $skill->id,
        ]);
    });
});

// ---------------------------------------------------------------------------
// Validation errors
// ---------------------------------------------------------------------------

describe('validation', function () {
    test('assigning already-assigned skill returns redirect with skill_id error', function () {
        $employee = skillUser('employee');
        $skill = Skill::factory()->create(['is_active' => true]);
        SkillAssignment::factory()->create([
            'user_id' => $employee->id,
            'skill_id' => $skill->id,
        ]);

        $this->actingAs($employee)
            ->post(route('users.skills.store', $employee), ['skill_id' => $skill->id])
            ->assertSessionHasErrors(['skill_id']);
    });

    test('assigning inactive skill returns redirect with skill_id error', function () {
        $employee = skillUser('employee');
        $skill = Skill::factory()->inactive()->create();

        $this->actingAs($employee)
            ->post(route('users.skills.store', $employee), ['skill_id' => $skill->id])
            ->assertSessionHasErrors(['skill_id']);
    });
});

// ---------------------------------------------------------------------------
// Access control
// ---------------------------------------------------------------------------

describe('access', function () {
    test('Employee attempts to assign skill to another user; returns 403', function () {
        $employee = skillUser('employee');
        $otherEmployee = skillUser('employee');
        $skill = Skill::factory()->create(['is_active' => true]);

        $this->actingAs($employee)
            ->post(route('users.skills.store', $otherEmployee), ['skill_id' => $skill->id])
            ->assertForbidden();
    });

    test('Employee attempts to remove a privileged-source assignment; returns 403', function () {
        $employee = skillUser('employee');
        $skill = Skill::factory()->create();
        SkillAssignment::factory()->privileged()->create([
            'user_id' => $employee->id,
            'skill_id' => $skill->id,
        ]);

        $this->actingAs($employee)
            ->delete(route('users.skills.destroy', [$employee, $skill]))
            ->assertForbidden();
    });

    test('Employee attempts to remove another user\'s assignment; returns 403', function () {
        $employee = skillUser('employee');
        $otherEmployee = skillUser('employee');
        $skill = Skill::factory()->create();
        SkillAssignment::factory()->create([
            'user_id' => $otherEmployee->id,
            'skill_id' => $skill->id,
            'source' => 'self',
        ]);

        $this->actingAs($employee)
            ->delete(route('users.skills.destroy', [$otherEmployee, $skill]))
            ->assertForbidden();
    });
});
