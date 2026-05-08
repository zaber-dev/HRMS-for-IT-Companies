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
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create an active HR user for task deadline property tests.
 */
function taskDeadlinePropHr(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('hr');
}

/**
 * Create an active employee user for task deadline property tests.
 */
function taskDeadlinePropEmployee(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('employee');
}

// ---------------------------------------------------------------------------
// Property 10: Task deadline cannot exceed project deadline
// ---------------------------------------------------------------------------

it('rejects a task_deadline after the project deadline on store and update', function () {
    // Feature: project-management, Property 10: task deadline cannot exceed project deadline
    $hr = taskDeadlinePropHr();

    // Random project deadline between 10 and 60 days from now
    $projectDeadlineDays = fake()->numberBetween(10, 60);
    $projectDeadlineDate = now()->addDays($projectDeadlineDays)->toDateString();

    $project = Project::factory()->create(['deadline' => $projectDeadlineDate]);

    $employee = taskDeadlinePropEmployee();

    // task_deadline is 1–30 days after the project deadline
    $overDays = fake()->numberBetween(1, 30);
    $taskDeadline = now()->addDays($projectDeadlineDays + $overDays)->toDateString();

    // Assert store rejects task_deadline after project deadline
    $this->actingAs($hr)
        ->post(route('projects.assignments.store', $project), [
            'user_id' => $employee->id,
            'task_description' => fake()->sentence(),
            'task_deadline' => $taskDeadline,
        ])
        ->assertSessionHasErrors('task_deadline');

    // Create a valid assignment to test the update path
    $validTaskDeadline = now()->addDays($projectDeadlineDays - 1)->toDateString();

    $assignment = ProjectAssignment::factory()->create([
        'project_id' => $project->id,
        'user_id' => $employee->id,
        'task_deadline' => $validTaskDeadline,
    ]);

    // Assert update rejects task_deadline after project deadline
    $this->actingAs($hr)
        ->put(route('projects.assignments.update', [$project, $assignment]), [
            'task_description' => fake()->sentence(),
            'task_deadline' => $taskDeadline,
        ])
        ->assertSessionHasErrors('task_deadline');
})->repeat(100);
