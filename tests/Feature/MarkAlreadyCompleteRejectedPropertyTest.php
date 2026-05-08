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
 * Create an active employee user for the already-complete rejection property tests.
 */
function alreadyCompleteEmployee(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('employee');
}

// ---------------------------------------------------------------------------
// Property 16: Marking an already-complete task is rejected
// ---------------------------------------------------------------------------

it('marking an already-complete task is rejected', function () {
    // Feature: project-management, Property 16: marking an already-complete task is rejected
    $employee = alreadyCompleteEmployee();

    $project = Project::factory()->create();

    $assignment = ProjectAssignment::factory()->create([
        'project_id' => $project->id,
        'user_id' => $employee->id,
        'completion_status' => CompletionStatus::Complete,
    ]);

    $this->actingAs($employee)
        ->withHeaders(['Accept' => 'application/json'])
        ->patch(route('my-projects.complete', $assignment))
        ->assertStatus(422);
})->repeat(100);
