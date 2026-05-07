<?php

use App\Models\Skill;
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
 * Create an active user with the given role.
 */
function categoryUser(string $role): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole($role);
}

// ---------------------------------------------------------------------------
// Happy paths
// ---------------------------------------------------------------------------

describe('store (happy path)', function () {
    test('HR creates a category; redirects to index and category exists in DB', function () {
        $hr = categoryUser('hr');

        $this->actingAs($hr)
            ->post(route('skill-categories.store'), [
                'name' => 'Engineering',
            ])
            ->assertRedirect(route('skill-categories.index'));

        $this->assertDatabaseHas('skill_categories', ['name' => 'Engineering']);
    });

    test('Admin creates a category; category exists in DB', function () {
        $admin = categoryUser('admin');

        $this->actingAs($admin)
            ->post(route('skill-categories.store'), [
                'name' => 'Leadership',
            ])
            ->assertRedirect(route('skill-categories.index'));

        $this->assertDatabaseHas('skill_categories', ['name' => 'Leadership']);
    });
});

describe('update (happy path)', function () {
    test('HR renames a category; redirect and category name updated in DB', function () {
        $hr = categoryUser('hr');
        $category = SkillCategory::factory()->create(['name' => 'Old Category']);

        $this->actingAs($hr)
            ->put(route('skill-categories.update', $category), [
                'name' => 'New Category',
            ])
            ->assertRedirect(route('skill-categories.index'));

        $this->assertDatabaseHas('skill_categories', ['id' => $category->id, 'name' => 'New Category']);
        $this->assertDatabaseMissing('skill_categories', ['name' => 'Old Category']);
    });

    test('HR renames a category; all associated skills reflect the new category name', function () {
        $hr = categoryUser('hr');
        $category = SkillCategory::factory()->create(['name' => 'Old Name']);
        $skills = Skill::factory()->count(3)->withCategory($category)->create();

        $this->actingAs($hr)
            ->put(route('skill-categories.update', $category), [
                'name' => 'Renamed Category',
            ])
            ->assertRedirect(route('skill-categories.index'));

        $category->refresh();
        expect($category->name)->toBe('Renamed Category');

        // All skills still belong to the same category, which now has the new name
        foreach ($skills as $skill) {
            $skill->refresh();
            expect($skill->skillCategory->name)->toBe('Renamed Category');
        }
    });

    test('updating a category with its own name does not return a duplicate error', function () {
        $hr = categoryUser('hr');
        $category = SkillCategory::factory()->create(['name' => 'My Category']);

        $this->actingAs($hr)
            ->put(route('skill-categories.update', $category), [
                'name' => 'My Category',
            ])
            ->assertRedirect(route('skill-categories.index'));
    });
});

// ---------------------------------------------------------------------------
// Validation errors
// ---------------------------------------------------------------------------

describe('validation — duplicate name', function () {
    test('duplicate name on store returns 422 with name error', function () {
        $hr = categoryUser('hr');
        SkillCategory::factory()->create(['name' => 'Duplicate Category']);

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('skill-categories.store'), [
                'name' => 'Duplicate Category',
            ])
            ->assertStatus(422)
            ->assertInvalid(['name']);
    });

    test('duplicate name on update returns 422 with name error', function () {
        $hr = categoryUser('hr');
        SkillCategory::factory()->create(['name' => 'Existing Category']);
        $category = SkillCategory::factory()->create(['name' => 'Another Category']);

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->put(route('skill-categories.update', $category), [
                'name' => 'Existing Category',
            ])
            ->assertStatus(422)
            ->assertInvalid(['name']);
    });
});

describe('validation — name length', function () {
    test('name of 1 character returns 422', function () {
        $hr = categoryUser('hr');

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('skill-categories.store'), ['name' => 'A'])
            ->assertStatus(422)
            ->assertInvalid(['name']);
    });

    test('name of 101 characters returns 422', function () {
        $hr = categoryUser('hr');

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('skill-categories.store'), ['name' => str_repeat('a', 101)])
            ->assertStatus(422)
            ->assertInvalid(['name']);
    });

    test('name of 2 characters is accepted', function () {
        $hr = categoryUser('hr');

        $this->actingAs($hr)
            ->post(route('skill-categories.store'), ['name' => 'AB'])
            ->assertRedirect(route('skill-categories.index'));
    });

    test('name of 100 characters is accepted', function () {
        $hr = categoryUser('hr');

        $this->actingAs($hr)
            ->post(route('skill-categories.store'), ['name' => str_repeat('a', 100)])
            ->assertRedirect(route('skill-categories.index'));
    });
});

// ---------------------------------------------------------------------------
// Access control
// ---------------------------------------------------------------------------

describe('access — employee is forbidden', function () {
    test('Employee attempting store returns 403', function () {
        $employee = categoryUser('employee');

        $this->actingAs($employee)
            ->post(route('skill-categories.store'), ['name' => 'Forbidden Category'])
            ->assertForbidden();
    });

    test('Employee attempting update returns 403', function () {
        $employee = categoryUser('employee');
        $category = SkillCategory::factory()->create();

        $this->actingAs($employee)
            ->put(route('skill-categories.update', $category), ['name' => 'Hacked Name'])
            ->assertForbidden();
    });
});

// ---------------------------------------------------------------------------
// No delete route
// ---------------------------------------------------------------------------

describe('no delete route', function () {
    test('DELETE /skill-categories/{id} returns 405 Method Not Allowed', function () {
        $hr = categoryUser('hr');
        $category = SkillCategory::factory()->create();

        $this->actingAs($hr)
            ->delete("/skill-categories/{$category->id}")
            ->assertStatus(405);
    });
});
