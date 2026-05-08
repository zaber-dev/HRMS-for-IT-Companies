<?php

// Feature: project-management, Property 2: project name length is enforced

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
 * Create an active privileged user for project name length property tests.
 */
function projectNameLengthUser(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('hr');
}

/**
 * Build a valid store payload with the given name.
 */
function projectNameLengthPayload(string $name): array
{
    return [
        'name' => $name,
        'status' => 'planning',
        'deadline' => now()->addMonth()->toDateString(),
    ];
}

// ---------------------------------------------------------------------------
// Property 2: Project name length is enforced — invalid lengths
// ---------------------------------------------------------------------------

it('rejects project names with invalid length (too short or too long)', function () {
    $user = projectNameLengthUser();

    // Randomly pick either a too-short length (0–1) or a too-long length (151–200)
    if (rand(0, 1) === 0) {
        $length = rand(0, 1);
    } else {
        $length = rand(151, 200);
    }

    $name = str_repeat('a', $length);

    $this->actingAs($user)
        ->post(route('projects.store'), projectNameLengthPayload($name))
        ->assertSessionHasErrors('name');
})->repeat(100);

// ---------------------------------------------------------------------------
// Property 2: Project name length is enforced — valid lengths (2–150)
// ---------------------------------------------------------------------------

it('accepts project names within the valid length range (length 2 to 150)', function () {
    $user = projectNameLengthUser();
    $length = rand(2, 150);

    // Use a unique suffix to avoid duplicate-name validation errors across iterations.
    // The suffix is at most 8 chars; pad with 'x' only when length allows it.
    $suffix = substr(fake()->unique()->lexify('????????'), 0, min(8, $length));
    $padding = max(0, $length - strlen($suffix));
    $name = str_repeat('x', $padding).$suffix;

    $this->actingAs($user)
        ->post(route('projects.store'), projectNameLengthPayload($name))
        ->assertSessionDoesntHaveErrors('name');
})->repeat(100);
