<?php

use App\Enums\BenchStatus;
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
 * Create an active HR user for the bench status retained property tests.
 */
function benchRetainedHrUser(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('hr');
}

/**
 * Create an active employee user with bench_status set to 'assigned'.
 */
function benchRetainedEmployee(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
        'bench_status' => BenchStatus::Assigned,
    ])->assignRole('employee');
}

// ---------------------------------------------------------------------------
// Property 14: Bench status stays assigned when other active assignments remain
// ---------------------------------------------------------------------------

it('bench status stays assigned when other active assignments remain after destroy', function () {
    // Feature: project-management, Property 14: bench status stays assigned when other active assignments remain
    $hr = benchRetainedHrUser();
    $employee = benchRetainedEmployee();

    // Create two or more pending assignments for the employee
    $projectA = Project::factory()->create();
    $projectB = Project::factory()->create();

    $assignmentToRemove = ProjectAssignment::factory()->create([
        'project_id' => $projectA->id,
        'user_id' => $employee->id,
        'completion_status' => CompletionStatus::Pending,
    ]);

    // Second pending assignment — ensures bench_status should remain 'assigned'
    ProjectAssignment::factory()->create([
        'project_id' => $projectB->id,
        'user_id' => $employee->id,
        'completion_status' => CompletionStatus::Pending,
    ]);

    // Remove one assignment via the destroy route (privileged user)
    $this->actingAs($hr)
        ->delete(route('projects.assignments.destroy', [$projectA, $assignmentToRemove]))
        ->assertRedirect(route('projects.show', $projectA));

    // The employee still has one pending assignment — bench_status must remain 'assigned'
    expect($employee->fresh()->bench_status)->toBe(BenchStatus::Assigned);
})->repeat(100);

it('bench status stays assigned when other active assignments remain after mark complete', function () {
    // Feature: project-management, Property 14: bench status stays assigned when other active assignments remain
    $employee = benchRetainedEmployee();

    // Create two or more pending assignments for the employee
    $projectA = Project::factory()->create();
    $projectB = Project::factory()->create();

    $assignmentToComplete = ProjectAssignment::factory()->create([
        'project_id' => $projectA->id,
        'user_id' => $employee->id,
        'completion_status' => CompletionStatus::Pending,
    ]);

    // Second pending assignment — ensures bench_status should remain 'assigned'
    ProjectAssignment::factory()->create([
        'project_id' => $projectB->id,
        'user_id' => $employee->id,
        'completion_status' => CompletionStatus::Pending,
    ]);

    // Employee marks one assignment complete via the markComplete route
    $this->actingAs($employee)
        ->patch(route('my-projects.complete', $assignmentToComplete))
        ->assertRedirect(route('my-projects.index'));

    // The employee still has one pending assignment — bench_status must remain 'assigned'
    expect($employee->fresh()->bench_status)->toBe(BenchStatus::Assigned);
})->repeat(100);
