<?php

use App\Enums\CompletionStatus;
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
 * Create an active employee user for the mark-complete status property tests.
 */
function markCompleteEmployee(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('employee');
}

// ---------------------------------------------------------------------------
// Property 15: Marking a task complete sets completion_status to complete
// ---------------------------------------------------------------------------

it('marking a task complete sets completion_status to complete', function () {
    // Feature: project-management, Property 15: marking a task complete sets completion_status to complete
    $employee = markCompleteEmployee();

    $project = Project::factory()->create();

    $assignment = ProjectAssignment::factory()->create([
        'project_id' => $project->id,
        'user_id' => $employee->id,
        'completion_status' => CompletionStatus::Pending,
    ]);

    $this->actingAs($employee)
        ->patch(route('my-projects.complete', $assignment))
        ->assertRedirect(route('my-projects.index'));

    expect($assignment->fresh()->completion_status)->toBe(CompletionStatus::Complete);
})->repeat(100);
