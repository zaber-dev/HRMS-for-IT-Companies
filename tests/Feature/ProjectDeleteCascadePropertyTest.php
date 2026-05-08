<?php

use App\Enums\BenchStatus;
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
 * Create an active HR user for the cascade delete property tests.
 */
function cascadeDeleteHrUser(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('hr');
}

/**
 * Create an active employee user with bench_status set to 'assigned'.
 */
function assignedEmployee(): User
{
    $employee = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
        'bench_status' => BenchStatus::Assigned,
    ])->assignRole('employee');

    return $employee;
}

// ---------------------------------------------------------------------------
// Property 6: Deleting a project removes all its assignments and recalculates bench status
// ---------------------------------------------------------------------------

it('deleting a project removes all its assignments and recalculates bench status', function () {
    // Feature: project-management, Property 6: deleting a project removes all its assignments and recalculates bench status
    $hr = cascadeDeleteHrUser();
    $project = Project::factory()->create();

    $assignmentCount = rand(1, 5);

    // Track employees who have ONLY this project's assignment (should become on_bench after delete)
    $soloEmployees = collect();

    // Track employees who have additional pending assignments on other projects (should stay assigned)
    $multiEmployees = collect();

    for ($i = 0; $i < $assignmentCount; $i++) {
        $employee = assignedEmployee();

        // Randomly decide if this employee has additional assignments on other projects
        $hasOtherAssignments = (bool) rand(0, 1);

        if ($hasOtherAssignments) {
            // Give this employee a pending assignment on a different project
            $otherProject = Project::factory()->create();
            ProjectAssignment::factory()->create([
                'project_id' => $otherProject->id,
                'user_id' => $employee->id,
                'completion_status' => 'pending',
            ]);
            $multiEmployees->push($employee);
        } else {
            $soloEmployees->push($employee);
        }

        // Assign the employee to the project under test
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employee->id,
            'completion_status' => 'pending',
        ]);
    }

    // Delete the project via the destroy route
    $this->actingAs($hr)
        ->delete(route('projects.destroy', $project))
        ->assertRedirect(route('projects.index'));

    // Assert zero ProjectAssignment records remain for the deleted project
    expect(ProjectAssignment::where('project_id', $project->id)->count())->toBe(0);

    // Assert employees whose only pending assignment was on the deleted project are now on_bench
    foreach ($soloEmployees as $employee) {
        expect($employee->fresh()->bench_status)->toBe(BenchStatus::OnBench);
    }

    // Assert employees with remaining pending assignments retain bench_status = 'assigned'
    foreach ($multiEmployees as $employee) {
        expect($employee->fresh()->bench_status)->toBe(BenchStatus::Assigned);
    }
})->repeat(100);
