<?php

use App\Enums\AssignmentSource;
use App\Models\Skill;
use App\Models\SkillAssignment;
use App\Models\SkillCategory;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create an active user with the given role for skills property tests.
 */
function skillsPropUser(string $role): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole($role);
}

// ---------------------------------------------------------------------------
// Property 1: Skill names are unique across the catalogue
// ---------------------------------------------------------------------------

it('rejects duplicate skill names on create and update', function () {
    // Feature: employee-skills-management, Property 1: skill names are unique across the catalogue
    $hr = skillsPropUser('hr');
    $category = SkillCategory::factory()->create();
    $existingName = fake()->unique()->words(3, true);

    Skill::factory()->withCategory($category)->create(['name' => $existingName]);

    // Attempt to create another skill with the same name
    $this->actingAs($hr)
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('skills.store'), [
            'name' => $existingName,
            'skill_category_id' => $category->id,
            'is_active' => true,
        ])
        ->assertStatus(422)
        ->assertInvalid(['name']);

    // Attempt to update a different skill to the same name
    $otherSkill = Skill::factory()->withCategory($category)->create();

    $this->actingAs($hr)
        ->withHeaders(['Accept' => 'application/json'])
        ->put(route('skills.update', $otherSkill), [
            'name' => $existingName,
            'skill_category_id' => $category->id,
            'is_active' => true,
        ])
        ->assertStatus(422)
        ->assertInvalid(['name']);
})->repeat(100);

// ---------------------------------------------------------------------------
// Property 2: Skill name length is enforced (dataset-driven)
// ---------------------------------------------------------------------------

it('enforces skill name length boundaries', function (int $length, bool $shouldPass) {
    // Feature: employee-skills-management, Property 2: skill name length is enforced
    $hr = skillsPropUser('hr');
    $category = SkillCategory::factory()->create();
    $name = str_repeat('a', $length);

    $response = $this->actingAs($hr)
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('skills.store'), [
            'name' => $name,
            'skill_category_id' => $category->id,
            'is_active' => true,
        ]);

    if ($shouldPass) {
        $response->assertRedirect(route('skills.index'));
    } else {
        $response->assertStatus(422)->assertInvalid(['name']);
    }
})->with([
    'length 0 (reject)' => [0, false],
    'length 1 (reject)' => [1, false],
    'length 2 (accept)' => [2, true],
    'length 50 (accept)' => [50, true],
    'length 100 (accept)' => [100, true],
    'length 101 (reject)' => [101, false],
    'length 200 (reject)' => [200, false],
]);

// ---------------------------------------------------------------------------
// Property 3: Employees cannot modify the skill catalogue
// ---------------------------------------------------------------------------

it('returns 403 when an employee attempts to modify the skill catalogue', function () {
    // Feature: employee-skills-management, Property 3: employees cannot modify the skill catalogue
    $employee = skillsPropUser('employee');
    $category = SkillCategory::factory()->create();
    $skill = Skill::factory()->withCategory($category)->create();

    // Attempt create
    $this->actingAs($employee)
        ->post(route('skills.store'), [
            'name' => fake()->unique()->words(2, true),
            'skill_category_id' => $category->id,
            'is_active' => true,
        ])
        ->assertForbidden();

    // Attempt update
    $this->actingAs($employee)
        ->put(route('skills.update', $skill), [
            'name' => fake()->unique()->words(2, true),
            'skill_category_id' => $category->id,
            'is_active' => true,
        ])
        ->assertForbidden();

    // Attempt delete
    $this->actingAs($employee)
        ->delete(route('skills.destroy', $skill))
        ->assertForbidden();

    // Attempt toggle
    $this->actingAs($employee)
        ->patch(route('skills.toggle', $skill))
        ->assertForbidden();
})->repeat(100);

// ---------------------------------------------------------------------------
// Property 4: Deleting a skill removes all its assignments
// ---------------------------------------------------------------------------

it('removes all assignments when a skill is deleted', function () {
    // Feature: employee-skills-management, Property 4: deleting a skill removes all its assignments
    $hr = skillsPropUser('hr');
    $skill = Skill::factory()->create();
    $count = rand(1, 10);

    SkillAssignment::factory()->count($count)->create(['skill_id' => $skill->id]);

    expect(SkillAssignment::where('skill_id', $skill->id)->count())->toBe($count);

    $this->actingAs($hr)
        ->delete(route('skills.destroy', $skill))
        ->assertRedirect(route('skills.index'));

    expect(SkillAssignment::where('skill_id', $skill->id)->count())->toBe(0);
})->repeat(100);

// ---------------------------------------------------------------------------
// Property 5: Category filter returns only matching skills
// ---------------------------------------------------------------------------

it('returns only skills belonging to the filtered category', function () {
    // Feature: employee-skills-management, Property 5: category filter returns only matching skills
    $hr = skillsPropUser('hr');
    $targetCategory = SkillCategory::factory()->create();
    $otherCategory = SkillCategory::factory()->create();

    $targetCount = rand(1, 5);
    $otherCount = rand(1, 5);

    Skill::factory()->count($targetCount)->withCategory($targetCategory)->create();
    Skill::factory()->count($otherCount)->withCategory($otherCategory)->create();

    $this->withoutVite()
        ->actingAs($hr)
        ->get(route('skills.index', ['category' => $targetCategory->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('skills.total', $targetCount)
        );
})->repeat(100);

// ---------------------------------------------------------------------------
// Property 6: Active status filter returns only matching skills
// ---------------------------------------------------------------------------

it('returns only skills matching the is_active filter', function () {
    // Feature: employee-skills-management, Property 6: active status filter returns only matching skills
    $hr = skillsPropUser('hr');

    $activeCount = rand(1, 5);
    $inactiveCount = rand(1, 5);

    Skill::factory()->count($activeCount)->create(['is_active' => true]);
    Skill::factory()->count($inactiveCount)->inactive()->create();

    // Filter active
    $this->withoutVite()
        ->actingAs($hr)
        ->get(route('skills.index', ['is_active' => 'true']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('skills.total', $activeCount)
        );

    // Filter inactive
    $this->withoutVite()
        ->actingAs($hr)
        ->get(route('skills.index', ['is_active' => 'false']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('skills.total', $inactiveCount)
        );
})->repeat(100);

// ---------------------------------------------------------------------------
// Property 7: A user cannot be assigned the same skill twice
// ---------------------------------------------------------------------------

it('rejects assigning the same skill to a user twice', function () {
    // Feature: employee-skills-management, Property 7: a user cannot be assigned the same skill twice
    $employee = skillsPropUser('employee');
    $hr = skillsPropUser('hr');
    $skill = Skill::factory()->create(['is_active' => true]);

    // First assignment succeeds
    SkillAssignment::factory()->create([
        'user_id' => $employee->id,
        'skill_id' => $skill->id,
        'source' => 'self',
    ]);

    // Employee attempts to assign again
    $this->actingAs($employee)
        ->post(route('users.skills.store', $employee), ['skill_id' => $skill->id])
        ->assertSessionHasErrors(['skill_id']);

    // Privileged user attempts to assign again
    $this->actingAs($hr)
        ->post(route('users.skills.store', $employee), ['skill_id' => $skill->id])
        ->assertSessionHasErrors(['skill_id']);
})->repeat(100);

// ---------------------------------------------------------------------------
// Property 8: Inactive skills cannot be assigned
// ---------------------------------------------------------------------------

it('rejects assigning an inactive skill', function () {
    // Feature: employee-skills-management, Property 8: inactive skills cannot be assigned
    $employee = skillsPropUser('employee');
    $hr = skillsPropUser('hr');
    $inactiveSkill = Skill::factory()->inactive()->create();

    // Employee attempts to assign inactive skill
    $this->actingAs($employee)
        ->post(route('users.skills.store', $employee), ['skill_id' => $inactiveSkill->id])
        ->assertSessionHasErrors(['skill_id']);

    // Privileged user attempts to assign inactive skill
    $this->actingAs($hr)
        ->post(route('users.skills.store', $employee), ['skill_id' => $inactiveSkill->id])
        ->assertSessionHasErrors(['skill_id']);
})->repeat(100);

// ---------------------------------------------------------------------------
// Property 9: Privileged assignments always record source as 'privileged'
// ---------------------------------------------------------------------------

it('records source as privileged when a privileged user assigns a skill', function (string $role) {
    // Feature: employee-skills-management, Property 9: privileged assignments always record source as 'privileged'
    $privilegedUser = skillsPropUser($role);
    $employee = skillsPropUser('employee');
    $skill = Skill::factory()->create(['is_active' => true]);

    $this->actingAs($privilegedUser)
        ->post(route('users.skills.store', $employee), ['skill_id' => $skill->id])
        ->assertRedirect(route('profile.edit'));

    $assignment = SkillAssignment::where('user_id', $employee->id)
        ->where('skill_id', $skill->id)
        ->first();

    expect($assignment)->not->toBeNull();
    expect($assignment->source)->toBe(AssignmentSource::Privileged);
})->with(['hr', 'admin', 'super_admin'])->repeat(100);

// ---------------------------------------------------------------------------
// Property 10: Employees cannot manage another user's skill assignments
// ---------------------------------------------------------------------------

it('returns 403 when an employee tries to manage another user\'s skill assignments', function () {
    // Feature: employee-skills-management, Property 10: employees cannot manage another user's skill assignments
    $employeeA = skillsPropUser('employee');
    $employeeB = skillsPropUser('employee');
    $skill = Skill::factory()->create(['is_active' => true]);

    // A attempts to assign skill to B
    $this->actingAs($employeeA)
        ->post(route('users.skills.store', $employeeB), ['skill_id' => $skill->id])
        ->assertForbidden();

    // Create an assignment on B so we can attempt removal
    SkillAssignment::factory()->create([
        'user_id' => $employeeB->id,
        'skill_id' => $skill->id,
        'source' => 'self',
    ]);

    // A attempts to remove skill from B
    $this->actingAs($employeeA)
        ->delete(route('users.skills.destroy', [$employeeB, $skill]))
        ->assertForbidden();
})->repeat(100);

// ---------------------------------------------------------------------------
// Property 11: Toggle is_active is a round-trip operation
// ---------------------------------------------------------------------------

it('returns a skill to its original is_active value after two toggles', function () {
    // Feature: employee-skills-management, Property 11: toggle is_active is a round-trip operation
    $hr = skillsPropUser('hr');
    $initialActive = (bool) rand(0, 1);
    $skill = Skill::factory()->create(['is_active' => $initialActive]);

    // First toggle
    $this->actingAs($hr)
        ->patch(route('skills.toggle', $skill));

    expect($skill->fresh()->is_active)->toBe(! $initialActive);

    // Second toggle
    $this->actingAs($hr)
        ->patch(route('skills.toggle', $skill));

    expect($skill->fresh()->is_active)->toBe($initialActive);
})->repeat(100);

// ---------------------------------------------------------------------------
// Property 12: Deactivating a skill preserves existing assignments
// ---------------------------------------------------------------------------

it('preserves all existing assignments when a skill is deactivated', function () {
    // Feature: employee-skills-management, Property 12: deactivating a skill preserves existing assignments
    $hr = skillsPropUser('hr');
    $skill = Skill::factory()->create(['is_active' => true]);
    $count = rand(1, 10);

    $assignments = SkillAssignment::factory()->count($count)->create(['skill_id' => $skill->id]);

    // Deactivate the skill via toggle
    $this->actingAs($hr)
        ->patch(route('skills.toggle', $skill));

    expect($skill->fresh()->is_active)->toBeFalse();

    // All assignments still exist with unchanged data
    foreach ($assignments as $assignment) {
        $this->assertDatabaseHas('skill_assignments', [
            'id' => $assignment->id,
            'user_id' => $assignment->user_id,
            'skill_id' => $assignment->skill_id,
            'source' => $assignment->source->value,
        ]);
    }

    expect(SkillAssignment::where('skill_id', $skill->id)->count())->toBe($count);
})->repeat(100);

// ---------------------------------------------------------------------------
// Property 13: Deactivated skills do not appear in the assignable skills list
// ---------------------------------------------------------------------------

it('excludes deactivated skills from the assignable skills list on the profile page', function () {
    // Feature: employee-skills-management, Property 13: deactivated skills do not appear in the assignable skills list
    $employee = skillsPropUser('employee');

    $activeSkill = Skill::factory()->create(['is_active' => true]);
    $inactiveSkill = Skill::factory()->inactive()->create();

    $this->withoutVite()
        ->actingAs($employee)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('availableSkills')
            ->where('availableSkills', function ($skills) use ($inactiveSkill, $activeSkill) {
                $skillIds = collect($skills)->pluck('id');

                // Inactive skill must not appear
                expect($skillIds->contains($inactiveSkill->id))->toBeFalse();

                // Active skill must appear (it's not yet assigned)
                expect($skillIds->contains($activeSkill->id))->toBeTrue();

                return true;
            })
        );
})->repeat(100);

// ---------------------------------------------------------------------------
// Property 14: Every skill belongs to exactly one category
// ---------------------------------------------------------------------------

it('creates skills with a non-null skill_category_id referencing an existing category', function () {
    // Feature: employee-skills-management, Property 14: every skill belongs to exactly one category
    $hr = skillsPropUser('hr');
    $category = SkillCategory::factory()->create();
    $name = fake()->unique()->words(3, true);

    $this->actingAs($hr)
        ->post(route('skills.store'), [
            'name' => $name,
            'skill_category_id' => $category->id,
            'is_active' => true,
        ])
        ->assertRedirect(route('skills.index'));

    $skill = Skill::where('name', $name)->first();

    expect($skill)->not->toBeNull();
    expect($skill->skill_category_id)->not->toBeNull();
    expect(SkillCategory::find($skill->skill_category_id))->not->toBeNull();
    expect($skill->skill_category_id)->toBe($category->id);
})->repeat(100);

// ---------------------------------------------------------------------------
// Property 15: Category rename propagates to all skills in that category
// ---------------------------------------------------------------------------

it('reflects the new category name on all skills after a category rename', function () {
    // Feature: employee-skills-management, Property 15: category rename propagates to all skills in that category
    $hr = skillsPropUser('hr');
    $category = SkillCategory::factory()->create();
    $count = rand(1, 10);

    $skills = Skill::factory()->count($count)->withCategory($category)->create();

    $newName = fake()->unique()->words(3, true);

    $this->actingAs($hr)
        ->put(route('skill-categories.update', $category), ['name' => $newName])
        ->assertRedirect(route('skill-categories.index'));

    $category->refresh();
    expect($category->name)->toBe($newName);

    // All skills in the category reflect the new name via their relation
    foreach ($skills as $skill) {
        $skill->refresh();
        $skill->load('skillCategory');
        expect($skill->skillCategory->name)->toBe($newName);
    }
})->repeat(100);

// ---------------------------------------------------------------------------
// Property 16: Skill category names are unique
// ---------------------------------------------------------------------------

it('rejects duplicate skill category names on create and update', function () {
    // Feature: employee-skills-management, Property 16: skill category names are unique
    $hr = skillsPropUser('hr');
    $existingName = fake()->unique()->words(3, true);

    SkillCategory::factory()->create(['name' => $existingName]);

    // Attempt to create another category with the same name
    $this->actingAs($hr)
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('skill-categories.store'), ['name' => $existingName])
        ->assertStatus(422)
        ->assertInvalid(['name']);

    // Attempt to rename a different category to the same name
    $otherCategory = SkillCategory::factory()->create();

    $this->actingAs($hr)
        ->withHeaders(['Accept' => 'application/json'])
        ->put(route('skill-categories.update', $otherCategory), ['name' => $existingName])
        ->assertStatus(422)
        ->assertInvalid(['name']);
})->repeat(100);

// ---------------------------------------------------------------------------
// Property 17: Employees cannot manage skill categories
// ---------------------------------------------------------------------------

it('returns 403 when an employee attempts to manage skill categories', function () {
    // Feature: employee-skills-management, Property 17: employees cannot manage skill categories
    $employee = skillsPropUser('employee');
    $category = SkillCategory::factory()->create();

    // Attempt to create a category
    $this->actingAs($employee)
        ->post(route('skill-categories.store'), ['name' => fake()->unique()->words(2, true)])
        ->assertForbidden();

    // Attempt to rename a category
    $this->actingAs($employee)
        ->put(route('skill-categories.update', $category), ['name' => fake()->unique()->words(2, true)])
        ->assertForbidden();
})->repeat(100);

// ---------------------------------------------------------------------------
// Property 18: Users without a defined role cannot access any skill route
// ---------------------------------------------------------------------------

it('returns 403 for roleless users on all skill routes', function () {
    // Feature: employee-skills-management, Property 18: users without a defined role cannot access any skill route
    $rolelessUser = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $skill = Skill::factory()->create();
    $category = SkillCategory::factory()->create();
    $targetUser = User::factory()->create(['is_active' => true, 'must_change_password' => false]);

    // GET /skills
    $this->actingAs($rolelessUser)
        ->get(route('skills.index'))
        ->assertForbidden();

    // POST /skills
    $this->actingAs($rolelessUser)
        ->post(route('skills.store'), [
            'name' => fake()->unique()->words(2, true),
            'skill_category_id' => $category->id,
            'is_active' => true,
        ])
        ->assertForbidden();

    // PUT /skills/{skill}
    $this->actingAs($rolelessUser)
        ->put(route('skills.update', $skill), [
            'name' => fake()->unique()->words(2, true),
            'skill_category_id' => $category->id,
            'is_active' => true,
        ])
        ->assertForbidden();

    // DELETE /skills/{skill}
    $this->actingAs($rolelessUser)
        ->delete(route('skills.destroy', $skill))
        ->assertForbidden();

    // PATCH /skills/{skill}/toggle
    $this->actingAs($rolelessUser)
        ->patch(route('skills.toggle', $skill))
        ->assertForbidden();

    // GET /skill-categories
    $this->actingAs($rolelessUser)
        ->get(route('skill-categories.index'))
        ->assertForbidden();

    // POST /skill-categories
    $this->actingAs($rolelessUser)
        ->post(route('skill-categories.store'), ['name' => fake()->unique()->words(2, true)])
        ->assertForbidden();

    // PUT /skill-categories/{category}
    $this->actingAs($rolelessUser)
        ->put(route('skill-categories.update', $category), ['name' => fake()->unique()->words(2, true)])
        ->assertForbidden();

    // POST /users/{user}/skills
    $this->actingAs($rolelessUser)
        ->post(route('users.skills.store', $targetUser), ['skill_id' => $skill->id])
        ->assertForbidden();

    // DELETE /users/{user}/skills/{skill}
    $this->actingAs($rolelessUser)
        ->delete(route('users.skills.destroy', [$targetUser, $skill]))
        ->assertForbidden();
})->repeat(100);
