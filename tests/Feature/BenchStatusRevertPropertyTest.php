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
 * Create an active HR user for the bench status revert property tests.
 */
function benchRevertHrUser(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('hr');
}

/**
 * Create an active employee user with bench_status set to 'assigned'.
 */
function benchRevertEmployee(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
        'bench_status' => BenchStatus::Assigned,
    ])->assignRole('employee');
}

// ---------------------------------------------------------------------------
// Property 13: Bench status reverts to on_bench when last active assignment is removed
// ---------------------------------------------------------------------------

it('bench status reverts to on_bench when last active assignment is removed', function () {
    // Feature: project-management, Property 13: bench status reverts to on_bench when last active assignment is removed or completed
    $hr = benchRevertHrUser();
    $employee = benchRevertEmployee();

    $project = Project::factory()->create();

    $assignment = ProjectAssignment::factory()->create([
        'project_id' => $project->id,
        'user_id' => $employee->id,
        'completion_status' => CompletionStatus::Pending,
    ]);

    $this->actingAs($hr)
        ->delete(route('projects.assignments.destroy', [$project, $assignment]))
        ->assertRedirect(route('projects.show', $project));

    expect($employee->fresh()->bench_status)->toBe(BenchStatus::OnBench);
})->repeat(50);

// ---------------------------------------------------------------------------
// Property 13: Bench status reverts to on_bench when last active assignment is completed
// ---------------------------------------------------------------------------

it('bench status reverts to on_bench when last active assignment is completed', function () {
    // Feature: project-management, Property 13: bench status reverts to on_bench when last active assignment is removed or completed
    $employee = benchRevertEmployee();

    $project = Project::factory()->create();

    $assignment = ProjectAssignment::factory()->create([
        'project_id' => $project->id,
        'user_id' => $employee->id,
        'completion_status' => CompletionStatus::Pending,
    ]);

    $this->actingAs($employee)
        ->patch(route('my-projects.complete', $assignment))
        ->assertRedirect(route('my-projects.index'));

    expect($employee->fresh()->bench_status)->toBe(BenchStatus::OnBench);
})->repeat(50);
