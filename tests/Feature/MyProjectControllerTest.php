<?php

use App\Enums\BenchStatus;
use App\Enums\CompletionStatus;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create an active user with the given role.
 */
function myProjectUser(string $role): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole($role);
}

// ---------------------------------------------------------------------------
// index
// ---------------------------------------------------------------------------

describe('index', function () {
    test('returns 200 for authenticated employee', function () {
        $employee = myProjectUser('employee');

        $this->withoutVite()
            ->actingAs($employee)
            ->get(route('my-projects.index'))
            ->assertOk();
    });

    test('shows only the authenticated employee\'s own assignments', function () {
        $employee = myProjectUser('employee');
        $otherEmployee = myProjectUser('employee');

        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();

        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employee->id,
        ]);

        ProjectAssignment::factory()->create([
            'project_id' => $otherProject->id,
            'user_id' => $otherEmployee->id,
        ]);

        $this->withoutVite()
            ->actingAs($employee)
            ->get(route('my-projects.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('projects/my-projects/index')
                ->where('assignments', fn ($assignments) => count($assignments) === 1
                    && $assignments[0]['user_id'] === $employee->id
                )
            );
    });

    test('displays employee\'s current bench_status', function () {
        $employee = myProjectUser('employee');
        $employee->bench_status = BenchStatus::Assigned;
        $employee->save();

        $project = Project::factory()->create();
        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employee->id,
        ]);

        $this->withoutVite()
            ->actingAs($employee)
            ->get(route('my-projects.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('projects/my-projects/index')
                ->where('assignments.0.user_id', $employee->id)
            );

        // Verify bench_status is accessible on the user
        expect($employee->fresh()->bench_status)->toBe(BenchStatus::Assigned);
    });
});

// ---------------------------------------------------------------------------
// markComplete
// ---------------------------------------------------------------------------

describe('markComplete', function () {
    test('sets completion_status to complete on the assignment record', function () {
        $employee = myProjectUser('employee');
        $project = Project::factory()->create();

        $assignment = ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employee->id,
            'completion_status' => CompletionStatus::Pending,
        ]);

        $this->actingAs($employee)
            ->patch(route('my-projects.complete', $assignment));

        expect($assignment->fresh()->completion_status)->toBe(CompletionStatus::Complete);
    });

    test('sets bench_status to on_bench when it was the employee\'s last pending assignment', function () {
        $employee = myProjectUser('employee');
        $employee->bench_status = BenchStatus::Assigned;
        $employee->save();

        $project = Project::factory()->create();

        $assignment = ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employee->id,
            'completion_status' => CompletionStatus::Pending,
        ]);

        $this->actingAs($employee)
            ->patch(route('my-projects.complete', $assignment));

        expect($employee->fresh()->bench_status)->toBe(BenchStatus::OnBench);
    });

    test('retains bench_status as assigned when other pending assignments remain', function () {
        $employee = myProjectUser('employee');
        $employee->bench_status = BenchStatus::Assigned;
        $employee->save();

        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();

        $assignmentToComplete = ProjectAssignment::factory()->create([
            'project_id' => $project1->id,
            'user_id' => $employee->id,
            'completion_status' => CompletionStatus::Pending,
        ]);

        // Second pending assignment — employee should stay assigned
        ProjectAssignment::factory()->create([
            'project_id' => $project2->id,
            'user_id' => $employee->id,
            'completion_status' => CompletionStatus::Pending,
        ]);

        $this->actingAs($employee)
            ->patch(route('my-projects.complete', $assignmentToComplete));

        expect($employee->fresh()->bench_status)->toBe(BenchStatus::Assigned);
    });

    test('redirects to my-projects.index with success flash on success', function () {
        $employee = myProjectUser('employee');
        $project = Project::factory()->create();

        $assignment = ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employee->id,
            'completion_status' => CompletionStatus::Pending,
        ]);

        $this->actingAs($employee)
            ->patch(route('my-projects.complete', $assignment))
            ->assertRedirect(route('my-projects.index'));
    });

    test('returns 422 when assignment is already complete', function () {
        $employee = myProjectUser('employee');
        $project = Project::factory()->create();

        $assignment = ProjectAssignment::factory()->complete()->create([
            'project_id' => $project->id,
            'user_id' => $employee->id,
        ]);

        $this->actingAs($employee)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch(route('my-projects.complete', $assignment))
            ->assertStatus(422)
            ->assertInvalid(['completion_status']);
    });

    test('returns 403 when employee attempts to complete another employee\'s assignment', function () {
        $employee = myProjectUser('employee');
        $otherEmployee = myProjectUser('employee');
        $project = Project::factory()->create();

        $assignment = ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $otherEmployee->id,
            'completion_status' => CompletionStatus::Pending,
        ]);

        $this->actingAs($employee)
            ->patch(route('my-projects.complete', $assignment))
            ->assertForbidden();
    });
});
