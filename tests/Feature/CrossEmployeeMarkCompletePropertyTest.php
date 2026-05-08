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
 * Create an active employee user for the cross-employee mark-complete property tests.
 */
function crossEmployeeMarkCompleteEmployee(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('employee');
}

// ---------------------------------------------------------------------------
// Property 17: Employees cannot mark another employee's task complete
// ---------------------------------------------------------------------------

it('employees cannot mark another employee\'s task complete', function () {
    // Feature: project-management, Property 17: employees cannot mark another employee's task complete
    $employeeA = crossEmployeeMarkCompleteEmployee();
    $employeeB = crossEmployeeMarkCompleteEmployee();

    $project = Project::factory()->create();

    $assignment = ProjectAssignment::factory()->create([
        'project_id' => $project->id,
        'user_id' => $employeeB->id,
        'completion_status' => CompletionStatus::Pending,
    ]);

    $this->actingAs($employeeA)
        ->patch(route('my-projects.complete', $assignment))
        ->assertStatus(403);
})->repeat(100);
