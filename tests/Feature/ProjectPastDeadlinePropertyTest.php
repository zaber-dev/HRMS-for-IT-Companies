<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Property 4: Past deadlines are rejected on project creation
// ---------------------------------------------------------------------------

it('rejects a past deadline when creating a project', function () {
    // Feature: project-management, Property 4: past deadlines are rejected on project creation
    $hr = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('hr');

    $pastDate = now()->subDays(fake()->numberBetween(1, 3650))->toDateString();

    $this->actingAs($hr)
        ->post(route('projects.store'), [
            'name' => fake()->unique()->words(3, true),
            'status' => 'planning',
            'deadline' => $pastDate,
        ])
        ->assertSessionHasErrors('deadline');
})->repeat(100);
