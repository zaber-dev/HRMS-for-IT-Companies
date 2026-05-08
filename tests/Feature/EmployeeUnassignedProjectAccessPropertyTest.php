<?php

use App\Models\Project;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create an active employee user for the unassigned project access property tests.
 */
function unassignedProjectEmployee(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('employee');
}

// ---------------------------------------------------------------------------
// Property 18: Employees cannot access projects they are not assigned to
// ---------------------------------------------------------------------------

it('employees cannot access projects they are not assigned to', function () {
    // Feature: project-management, Property 18: employees cannot access projects they are not assigned to
    $employee = unassignedProjectEmployee();

    $project = Project::factory()->create();

    // Employee has no assignment on this project — must receive 403
    $this->actingAs($employee)
        ->get(route('projects.show', $project))
        ->assertForbidden();
})->repeat(100);
