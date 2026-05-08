<?php

use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Property 11: Duplicate assignment is rejected
// ---------------------------------------------------------------------------

it('rejects a second assignment of the same employee to the same project', function () {
    // Feature: project-management, Property 11: duplicate assignment is rejected
    $hr = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('hr');

    $employee = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('employee');

    $project = Project::factory()->create();

    // First assignment — should succeed
    ProjectAssignment::factory()->create([
        'project_id' => $project->id,
        'user_id' => $employee->id,
    ]);

    // Second assignment attempt — should be rejected with user_id error
    $this->actingAs($hr)
        ->post(route('projects.assignments.store', $project), [
            'user_id' => $employee->id,
            'task_description' => fake()->sentence(),
            'task_deadline' => now()->addDays(rand(1, 10))->toDateString(),
        ])
        ->assertSessionHasErrors('user_id');
})->repeat(100);
