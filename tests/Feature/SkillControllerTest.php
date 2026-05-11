<?php

use App\Enums\AssignmentSource;
use App\Models\Skill;
use App\Models\SkillAssignment;
use App\Models\SkillCategory;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Happy paths — CRUD
// ---------------------------------------------------------------------------

describe('store (happy path)', function () {
    test('HR creates a skill; redirects to index and skill exists in DB', function () {
        $hr = skillUser('hr');
        $category = SkillCategory::factory()->create();

        $this->actingAs($hr)
            ->post(route('skills.store'), [
                'name' => 'Laravel Development',
                'skill_category_id' => $category->id,
                'description' => 'Building apps with Laravel',
                'is_active' => true,
            ])
            ->assertRedirect(route('skills.index'));

        $this->assertDatabaseHas('skills', [
            'name' => 'Laravel Development',
            'skill_category_id' => $category->id,
            'is_active' => true,
        ]);
    });

    test('Admin creates a skill; skill exists in DB', function () {
        $admin = skillUser('admin');
        $category = SkillCategory::factory()->create();

        $this->actingAs($admin)
            ->post(route('skills.store'), [
                'name' => 'Project Management',
                'skill_category_id' => $category->id,
                'is_active' => true,
            ])
            ->assertRedirect(route('skills.index'));

        $this->assertDatabaseHas('skills', ['name' => 'Project Management']);
    });
});

describe('update (happy path)', function () {
    test('HR updates a skill; changes are persisted', function () {
        $hr = skillUser('hr');
        $category = SkillCategory::factory()->create();
        $newCategory = SkillCategory::factory()->create();
        $skill = Skill::factory()->withCategory($category)->create(['name' => 'Old Name']);

        $this->actingAs($hr)
            ->put(route('skills.update', $skill), [
                'name' => 'New Name',
                'skill_category_id' => $newCategory->id,
                'description' => 'Updated description',
                'is_active' => false,
            ])
            ->assertRedirect(route('skills.index'));

        $skill->refresh();
        expect($skill->name)->toBe('New Name');
        expect($skill->skill_category_id)->toBe($newCategory->id);
        expect($skill->description)->toBe('Updated description');
        expect($skill->is_active)->toBeFalse();
    });
});

describe('destroy (happy path)', function () {
    test('HR deletes a skill with assignments; skill and all assignments are removed', function () {
        $hr = skillUser('hr');
        $skill = Skill::factory()->create();

        // Create multiple assignments for this skill
        SkillAssignment::factory()->count(3)->create(['skill_id' => $skill->id]);

        $this->actingAs($hr)
            ->delete(route('skills.destroy', $skill))
            ->assertRedirect(route('skills.index'));

        $this->assertDatabaseMissing('skills', ['id' => $skill->id]);
        $this->assertDatabaseEmpty('skill_assignments');
    });
});

describe('toggle (happy path)', function () {
    test('HR toggles is_active on an active skill; flag is flipped to false', function () {
        $hr = skillUser('hr');
        $skill = Skill::factory()->create(['is_active' => true]);

        $this->actingAs($hr)
            ->patch(route('skills.toggle', $skill));

        expect($skill->fresh()->is_active)->toBeFalse();
    });

    test('HR toggles is_active on an inactive skill; flag is flipped to true', function () {
        $hr = skillUser('hr');
        $skill = Skill::factory()->inactive()->create();

        $this->actingAs($hr)
            ->patch(route('skills.toggle', $skill));

        expect($skill->fresh()->is_active)->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// Validation errors
// ---------------------------------------------------------------------------

describe('validation — duplicate name', function () {
    test('duplicate name on store returns 422 with name error', function () {
        $hr = skillUser('hr');
        $category = SkillCategory::factory()->create();
        Skill::factory()->withCategory($category)->create(['name' => 'Duplicate Skill']);

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('skills.store'), [
                'name' => 'Duplicate Skill',
                'skill_category_id' => $category->id,
                'is_active' => true,
            ])
            ->assertStatus(422)
            ->assertInvalid(['name']);
    });

    test('duplicate name on update returns 422 with name error', function () {
        $hr = skillUser('hr');
        $category = SkillCategory::factory()->create();
        Skill::factory()->withCategory($category)->create(['name' => 'Existing Skill']);
        $skill = Skill::factory()->withCategory($category)->create(['name' => 'Another Skill']);

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->put(route('skills.update', $skill), [
                'name' => 'Existing Skill',
                'skill_category_id' => $category->id,
                'is_active' => true,
            ])
            ->assertStatus(422)
            ->assertInvalid(['name']);
    });

    test('updating a skill with its own name does not return a duplicate error', function () {
        $hr = skillUser('hr');
        $category = SkillCategory::factory()->create();
        $skill = Skill::factory()->withCategory($category)->create(['name' => 'My Skill']);

        $this->actingAs($hr)
            ->put(route('skills.update', $skill), [
                'name' => 'My Skill',
                'skill_category_id' => $category->id,
                'is_active' => true,
            ])
            ->assertRedirect(route('skills.index'));
    });
});

describe('validation — name length', function () {
    test('name of 1 character returns 422', function () {
        $hr = skillUser('hr');
        $category = SkillCategory::factory()->create();

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('skills.store'), [
                'name' => 'A',
                'skill_category_id' => $category->id,
                'is_active' => true,
            ])
            ->assertStatus(422)
            ->assertInvalid(['name']);
    });

    test('name of 101 characters returns 422', function () {
        $hr = skillUser('hr');
        $category = SkillCategory::factory()->create();

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('skills.store'), [
                'name' => str_repeat('a', 101),
                'skill_category_id' => $category->id,
                'is_active' => true,
            ])
            ->assertStatus(422)
            ->assertInvalid(['name']);
    });

    test('name of 2 characters is accepted', function () {
        $hr = skillUser('hr');
        $category = SkillCategory::factory()->create();

        $this->actingAs($hr)
            ->post(route('skills.store'), [
                'name' => 'AB',
                'skill_category_id' => $category->id,
                'is_active' => true,
            ])
            ->assertRedirect(route('skills.index'));
    });

    test('name of 100 characters is accepted', function () {
        $hr = skillUser('hr');
        $category = SkillCategory::factory()->create();

        $this->actingAs($hr)
            ->post(route('skills.store'), [
                'name' => str_repeat('a', 100),
                'skill_category_id' => $category->id,
                'is_active' => true,
            ])
            ->assertRedirect(route('skills.index'));
    });
});

describe('validation — missing category', function () {
    test('missing skill_category_id on store returns 422 with skill_category_id error', function () {
        $hr = skillUser('hr');

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('skills.store'), [
                'name' => 'Some Skill',
                'is_active' => true,
            ])
            ->assertStatus(422)
            ->assertInvalid(['skill_category_id']);
    });

    test('non-existent skill_category_id on store returns 422', function () {
        $hr = skillUser('hr');

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('skills.store'), [
                'name' => 'Some Skill',
                'skill_category_id' => 99999,
                'is_active' => true,
            ])
            ->assertStatus(422)
            ->assertInvalid(['skill_category_id']);
    });
});

// ---------------------------------------------------------------------------
// Access control
// ---------------------------------------------------------------------------

describe('access — employee is forbidden', function () {
    test('Employee attempting store returns 403', function () {
        $employee = skillUser('employee');
        $category = SkillCategory::factory()->create();

        $this->actingAs($employee)
            ->post(route('skills.store'), [
                'name' => 'Forbidden Skill',
                'skill_category_id' => $category->id,
                'is_active' => true,
            ])
            ->assertForbidden();
    });

    test('Employee attempting update returns 403', function () {
        $employee = skillUser('employee');
        $skill = Skill::factory()->create();

        $this->actingAs($employee)
            ->put(route('skills.update', $skill), [
                'name' => 'Hacked Name',
                'skill_category_id' => $skill->skill_category_id,
                'is_active' => true,
            ])
            ->assertForbidden();
    });

    test('Employee attempting destroy returns 403', function () {
        $employee = skillUser('employee');
        $skill = Skill::factory()->create();

        $this->actingAs($employee)
            ->delete(route('skills.destroy', $skill))
            ->assertForbidden();
    });

    test('Employee attempting toggle returns 403', function () {
        $employee = skillUser('employee');
        $skill = Skill::factory()->create();

        $this->actingAs($employee)
            ->patch(route('skills.toggle', $skill))
            ->assertForbidden();
    });
});

describe('access — unauthenticated', function () {
    test('unauthenticated request to index redirects to login', function () {
        $this->get(route('skills.index'))
            ->assertRedirect(route('login'));
    });

    test('unauthenticated request to create redirects to login', function () {
        $this->get(route('skills.create'))
            ->assertRedirect(route('login'));
    });
});

// ---------------------------------------------------------------------------
// Filters
// ---------------------------------------------------------------------------

describe('filters', function () {
    test('category filter returns only skills in that category', function () {
        $hr = skillUser('hr');
        $categoryA = SkillCategory::factory()->create();
        $categoryB = SkillCategory::factory()->create();

        Skill::factory()->count(3)->withCategory($categoryA)->create();
        Skill::factory()->count(2)->withCategory($categoryB)->create();

        $this->withoutVite()
            ->actingAs($hr)
            ->get(route('skills.index', ['category' => $categoryA->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('skills.total', 3)
            );
    });

    test('is_active=true filter returns only active skills', function () {
        $hr = skillUser('hr');

        Skill::factory()->count(4)->create(['is_active' => true]);
        Skill::factory()->count(2)->inactive()->create();

        $this->withoutVite()
            ->actingAs($hr)
            ->get(route('skills.index', ['is_active' => 'true']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('skills.total', 4)
            );
    });

    test('is_active=false filter returns only inactive skills', function () {
        $hr = skillUser('hr');

        Skill::factory()->count(3)->create(['is_active' => true]);
        Skill::factory()->count(2)->inactive()->create();

        $this->withoutVite()
            ->actingAs($hr)
            ->get(route('skills.index', ['is_active' => 'false']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('skills.total', 2)
            );
    });

    test('no filter returns all skills', function () {
        $hr = skillUser('hr');

        Skill::factory()->count(3)->create(['is_active' => true]);
        Skill::factory()->count(2)->inactive()->create();

        $this->withoutVite()
            ->actingAs($hr)
            ->get(route('skills.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('skills.total', 5)
            );
    });
});

// ---------------------------------------------------------------------------
// Assignment props
// ---------------------------------------------------------------------------

describe('assignment props', function () {
    test('index includes only the current user assignment source', function () {
        $employee = skillUser('employee');
        $otherEmployee = skillUser('employee');
        $skill = Skill::factory()->create();

        SkillAssignment::factory()->create([
            'user_id' => $employee->id,
            'skill_id' => $skill->id,
            'source' => AssignmentSource::Self,
        ]);

        SkillAssignment::factory()->privileged()->create([
            'user_id' => $otherEmployee->id,
            'skill_id' => $skill->id,
        ]);

        $this->withoutVite()
            ->actingAs($employee)
            ->get(route('skills.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('skills/index')
                ->has('skills.data', 1)
                ->where('skills.data.0.id', $skill->id)
                ->has('skills.data.0.assignments', 1)
                ->where('skills.data.0.assignments.0.user_id', $employee->id)
                ->where('skills.data.0.assignments.0.source', AssignmentSource::Self->value)
            );
    });
});
