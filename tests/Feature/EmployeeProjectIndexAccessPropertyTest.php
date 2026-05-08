<?php

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
 * Create an active employee user for the project index access property tests.
 */
function employeeProjectIndexUser(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('employee');
}

// ---------------------------------------------------------------------------
// Property 19: Employees cannot access the project index
// ---------------------------------------------------------------------------

it('employees cannot access the project index', function () {
    // Feature: project-management, Property 19: employees cannot access the project index
    $employee = employeeProjectIndexUser();

    $this->actingAs($employee)
        ->get(route('projects.index'))
        ->assertForbidden();
})->repeat(100);
