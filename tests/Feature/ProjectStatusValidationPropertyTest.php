<?php

// Feature: project-management, Property 3: invalid status values are rejected

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
 * Create an active privileged user with the given role.
 */
function statusPropUser(string $role): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole($role);
}

/**
 * Generate a random string that is not a valid ProjectStatus value.
 */
function invalidProjectStatus(): string
{
    $validStatuses = ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled'];

    do {
        $candidate = 'invalid_'.fake()->word();
    } while (in_array($candidate, $validStatuses, true));

    return $candidate;
}

// ---------------------------------------------------------------------------
// Property 3: Invalid status values are rejected
// ---------------------------------------------------------------------------

it('rejects invalid status values on store and update', function () {
    $hr = statusPropUser('hr');
    $invalidStatus = invalidProjectStatus();

    // store: POST /projects with an invalid status must return a session error on 'status'
    $this->actingAs($hr)
        ->post(route('projects.store'), [
            'name' => fake()->unique()->words(3, true),
            'status' => $invalidStatus,
            'deadline' => now()->addDays(30)->toDateString(),
        ])
        ->assertSessionHasErrors('status');

    // update: PUT /projects/{project} with an invalid status must return a session error on 'status'
    $project = Project::factory()->create();

    $this->actingAs($hr)
        ->put(route('projects.update', $project), [
            'name' => fake()->unique()->words(3, true),
            'status' => $invalidStatus,
            'deadline' => now()->addDays(30)->toDateString(),
        ])
        ->assertSessionHasErrors('status');
})->repeat(100);
