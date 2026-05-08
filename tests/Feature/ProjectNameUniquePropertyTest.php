<?php

use App\Models\Project;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create an active HR user for project name uniqueness property tests.
 */
function projectNamePropUser(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('hr');
}

// ---------------------------------------------------------------------------
// Property 1: Project names are unique
// ---------------------------------------------------------------------------

it('rejects duplicate project names on store and update', function () {
    // Feature: project-management, Property 1: project names are unique
    $hr = projectNamePropUser();
    $existingName = fake()->unique()->words(3, true);

    Project::factory()->create(['name' => $existingName]);

    // Attempt to create another project with the same name
    $this->actingAs($hr)
        ->post(route('projects.store'), [
            'name' => $existingName,
            'status' => 'planning',
            'deadline' => now()->addMonth()->toDateString(),
        ])
        ->assertSessionHasErrors('name');

    // Attempt to update a different project to the same name
    $otherProject = Project::factory()->create();

    $this->actingAs($hr)
        ->put(route('projects.update', $otherProject), [
            'name' => $existingName,
            'status' => 'planning',
            'deadline' => now()->addMonth()->toDateString(),
        ])
        ->assertSessionHasErrors('name');
})->repeat(100);
