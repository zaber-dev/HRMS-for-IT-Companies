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
// Property 5: Employees cannot create, edit, or delete projects
// ---------------------------------------------------------------------------

it('returns 403 when an employee attempts to create, update, or delete a project', function () {
    // Feature: project-management, Property 5: employees cannot create, edit, or delete projects
    $employee = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('employee');

    $project = Project::factory()->create();

    // POST projects.store → 403
    $this->actingAs($employee)
        ->post(route('projects.store'), [
            'name' => fake()->unique()->words(3, true),
            'status' => 'planning',
            'deadline' => now()->addDays(rand(1, 365))->toDateString(),
        ])
        ->assertForbidden();

    // PUT projects.update → 403
    $this->actingAs($employee)
        ->put(route('projects.update', $project), [
            'name' => fake()->unique()->words(3, true),
            'status' => 'planning',
            'deadline' => now()->addDays(rand(1, 365))->toDateString(),
        ])
        ->assertForbidden();

    // DELETE projects.destroy → 403
    $this->actingAs($employee)
        ->delete(route('projects.destroy', $project))
        ->assertForbidden();
})->repeat(100);
