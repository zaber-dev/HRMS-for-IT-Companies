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
// Property 20: Employees cannot manage assignments
// ---------------------------------------------------------------------------

it('returns 403 when an employee attempts to create, update, or delete an assignment', function () {
    // Feature: project-management, Property 20: employees cannot manage assignments
    $employee = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('employee');

    $project = Project::factory()->create();

    $assignment = ProjectAssignment::factory()->create([
        'project_id' => $project->id,
    ]);

    // POST projects.assignments.store → 403
    $this->actingAs($employee)
        ->post(route('projects.assignments.store', $project), [
            'user_id' => $employee->id,
            'task_description' => fake()->sentence(),
            'task_deadline' => now()->addDays(rand(1, 10))->toDateString(),
        ])
        ->assertForbidden();

    // PUT projects.assignments.update → 403
    $this->actingAs($employee)
        ->put(route('projects.assignments.update', [$project, $assignment]), [
            'task_description' => fake()->sentence(),
            'task_deadline' => now()->addDays(rand(1, 10))->toDateString(),
        ])
        ->assertForbidden();

    // DELETE projects.assignments.destroy → 403
    $this->actingAs($employee)
        ->delete(route('projects.assignments.destroy', [$project, $assignment]))
        ->assertForbidden();
})->repeat(100);
