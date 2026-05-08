<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create an active user with the given role and randomised name/email.
 */
function dashPropUser(string $role): User
{
    return User::factory()->create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole($role);
}

// ---------------------------------------------------------------------------
// Property 1: Role routing is correct
// Feature: dashboard-analytics, Property 1: role routing is correct
// ---------------------------------------------------------------------------

it('renders dashboard/hr for privileged roles', function (string $role) {
    $user = dashPropUser($role);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/hr'));
})->with([
    'hr',
    'admin',
    'super_admin',
])->repeat(34);
// Feature: dashboard-analytics, Property 1: role routing is correct

it('renders dashboard/employee for the employee role', function () {
    $user = dashPropUser('employee');

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/employee'));
})->repeat(100);
// Feature: dashboard-analytics, Property 1: role routing is correct
