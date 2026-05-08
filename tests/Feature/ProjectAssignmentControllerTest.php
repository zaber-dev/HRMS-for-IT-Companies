<?php

use App\Enums\BenchStatus;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Skill;
use App\Models\User;
use Carbon\Carbon;
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
function assignmentUser(string $role): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole($role);
}

// ---------------------------------------------------------------------------
// create
// ---------------------------------------------------------------------------

describe('create', function () {
    test('returns 200 for hr role and response includes suggestions and employees', function () {
        $hr = assignmentUser('hr');
        $project = Project::factory()->create();

        $response = $this->withoutVite()
            ->actingAs($hr)
            ->get(route('projects.assignments.create', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('suggestions')
            ->has('employees')
        );
    });

    test('returns 200 for admin role', function () {
        $admin = assignmentUser('admin');
        $project = Project::factory()->create();

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('projects.assignments.create', $project))
            ->assertOk();
    });

    test('returns 200 for super_admin role', function () {
        $superAdmin = assignmentUser('super_admin');
        $project = Project::factory()->create();

        $this->withoutVite()
            ->actingAs($superAdmin)
            ->get(route('projects.assignments.create', $project))
            ->assertOk();
    });

    test('suggestions include on-bench employees with matching skills', function () {
        $hr = assignmentUser('hr');
        $skill = Skill::factory()->create(['is_active' => true]);
        $project = Project::factory()->create();
        $project->skills()->attach($skill);

        // Employee on bench with matching skill — should appear in suggestions
        $matchingEmployee = assignmentUser('employee');
        $matchingEmployee->update(['bench_status' => BenchStatus::OnBench]);
        $matchingEmployee->skillAssignments()->create(['skill_id' => $skill->id, 'source' => 'privileged']);

        // Employee on bench without matching skill — should NOT appear in suggestions
        $noSkillEmployee = assignmentUser('employee');
        $noSkillEmployee->update(['bench_status' => BenchStatus::OnBench]);

        // Employee with matching skill but already assigned — should NOT appear in suggestions
        $assignedEmployee = assignmentUser('employee');
        $assignedEmployee->update(['bench_status' => BenchStatus::Assigned]);
        $assignedEmployee->skillAssignments()->create(['skill_id' => $skill->id, 'source' => 'privileged']);

        $response = $this->withoutVite()
            ->actingAs($hr)
            ->get(route('projects.assignments.create', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('suggestions', 1)
            ->where('suggestions.0.id', $matchingEmployee->id)
        );
    });

    test('suggestions are empty when no employees satisfy criteria', function () {
        $hr = assignmentUser('hr');
        $skill = Skill::factory()->create(['is_active' => true]);
        $project = Project::factory()->create();
        $project->skills()->attach($skill);

        // Employee on bench but no matching skill
        $employee = assignmentUser('employee');
        $employee->update(['bench_status' => BenchStatus::OnBench]);

        $response = $this->withoutVite()
            ->actingAs($hr)
            ->get(route('projects.assignments.create', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('suggestions', 0)
        );
    });

    test('returns 403 for employee role', function () {
        $employee = assignmentUser('employee');
        $project = Project::factory()->create();

        $this->actingAs($employee)
            ->get(route('projects.assignments.create', $project))
            ->assertForbidden();
    });
});

// ---------------------------------------------------------------------------
// store — happy path
// ---------------------------------------------------------------------------

describe('store (happy path)', function () {
    test('creates assignment, sets bench_status to assigned, redirects to project show', function () {
        $hr = assignmentUser('hr');
        $project = Project::factory()->create();
        $employee = assignmentUser('employee');
        $employee->update(['bench_status' => BenchStatus::OnBench]);

        $response = $this->actingAs($hr)
            ->post(route('projects.assignments.store', $project), [
                'user_id' => $employee->id,
                'task_description' => 'Build the API endpoints',
                'task_deadline' => now()->addDays(10)->toDateString(),
            ]);

        $response->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('project_assignments', [
            'project_id' => $project->id,
            'user_id' => $employee->id,
        ]);

        expect($employee->fresh()->bench_status)->toBe(BenchStatus::Assigned);
    });
});

// ---------------------------------------------------------------------------
// store — validation errors
// ---------------------------------------------------------------------------

describe('store — validation: duplicate assignment', function () {
    test('returns 422 for employee already assigned to the project', function () {
        $hr = assignmentUser('hr');
        $project = Project::factory()->create();
        $employee = assignmentUser('employee');

        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employee->id,
        ]);

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('projects.assignments.store', $project), [
                'user_id' => $employee->id,
                'task_description' => 'Duplicate task',
                'task_deadline' => now()->addDays(10)->toDateString(),
            ])
            ->assertStatus(422)
            ->assertInvalid(['user_id']);
    });
});

describe('store — validation: task_deadline after project deadline', function () {
    test('returns 422 when task_deadline is after project deadline', function () {
        $hr = assignmentUser('hr');
        $project = Project::factory()->withDeadline(Carbon::now()->addDays(20))->create();
        $employee = assignmentUser('employee');

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('projects.assignments.store', $project), [
                'user_id' => $employee->id,
                'task_description' => 'Some task',
                'task_deadline' => now()->addDays(30)->toDateString(),
            ])
            ->assertStatus(422)
            ->assertInvalid(['task_deadline']);
    });

    test('accepts task_deadline equal to project deadline', function () {
        $hr = assignmentUser('hr');
        $deadline = Carbon::now()->addDays(20);
        $project = Project::factory()->withDeadline($deadline)->create();
        $employee = assignmentUser('employee');

        $response = $this->actingAs($hr)
            ->post(route('projects.assignments.store', $project), [
                'user_id' => $employee->id,
                'task_description' => 'Some task',
                'task_deadline' => $deadline->toDateString(),
            ]);

        $response->assertRedirect(route('projects.show', $project));
    });
});

describe('store — access control', function () {
    test('returns 403 for employee role', function () {
        $employee = assignmentUser('employee');
        $project = Project::factory()->create();
        $otherEmployee = assignmentUser('employee');

        $this->actingAs($employee)
            ->post(route('projects.assignments.store', $project), [
                'user_id' => $otherEmployee->id,
                'task_description' => 'Forbidden task',
                'task_deadline' => now()->addDays(10)->toDateString(),
            ])
            ->assertForbidden();
    });
});

// ---------------------------------------------------------------------------
// edit
// ---------------------------------------------------------------------------

describe('edit', function () {
    test('returns 200 for hr role', function () {
        $hr = assignmentUser('hr');
        $project = Project::factory()->create();
        $employee = assignmentUser('employee');
        $assignment = ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employee->id,
        ]);

        $this->withoutVite()
            ->actingAs($hr)
            ->get(route('projects.assignments.edit', [$project, $assignment]))
            ->assertOk();
    });

    test('returns 200 for admin role', function () {
        $admin = assignmentUser('admin');
        $project = Project::factory()->create();
        $employee = assignmentUser('employee');
        $assignment = ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employee->id,
        ]);

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('projects.assignments.edit', [$project, $assignment]))
            ->assertOk();
    });

    test('returns 200 for super_admin role', function () {
        $superAdmin = assignmentUser('super_admin');
        $project = Project::factory()->create();
        $employee = assignmentUser('employee');
        $assignment = ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employee->id,
        ]);

        $this->withoutVite()
            ->actingAs($superAdmin)
            ->get(route('projects.assignments.edit', [$project, $assignment]))
            ->assertOk();
    });
});

// ---------------------------------------------------------------------------
// update — happy path
// ---------------------------------------------------------------------------

describe('update (happy path)', function () {
    test('updates task_description and task_deadline and redirects to project show', function () {
        $hr = assignmentUser('hr');
        $project = Project::factory()->withDeadline(Carbon::now()->addDays(30))->create();
        $employee = assignmentUser('employee');
        $assignment = ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employee->id,
            'task_description' => 'Old description',
            'task_deadline' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this->actingAs($hr)
            ->put(route('projects.assignments.update', [$project, $assignment]), [
                'task_description' => 'Updated description',
                'task_deadline' => now()->addDays(20)->toDateString(),
            ]);

        $response->assertRedirect(route('projects.show', $project));

        $assignment->refresh();
        expect($assignment->task_description)->toBe('Updated description');
        expect($assignment->task_deadline->toDateString())->toBe(now()->addDays(20)->toDateString());
    });
});

// ---------------------------------------------------------------------------
// update — validation errors
// ---------------------------------------------------------------------------

describe('update — validation: task_deadline after project deadline', function () {
    test('returns 422 when updated task_deadline is after project deadline', function () {
        $hr = assignmentUser('hr');
        $project = Project::factory()->withDeadline(Carbon::now()->addDays(20))->create();
        $employee = assignmentUser('employee');
        $assignment = ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employee->id,
        ]);

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->put(route('projects.assignments.update', [$project, $assignment]), [
                'task_description' => 'Some task',
                'task_deadline' => now()->addDays(30)->toDateString(),
            ])
            ->assertStatus(422)
            ->assertInvalid(['task_deadline']);
    });
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

describe('destroy', function () {
    test('removes assignment record and redirects to project show', function () {
        $hr = assignmentUser('hr');
        $project = Project::factory()->create();
        $employee = assignmentUser('employee');
        $assignment = ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employee->id,
        ]);

        $this->actingAs($hr)
            ->delete(route('projects.assignments.destroy', [$project, $assignment]))
            ->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseMissing('project_assignments', ['id' => $assignment->id]);
    });

    test('sets bench_status to on_bench when removed assignment was employee\'s only pending assignment', function () {
        $hr = assignmentUser('hr');
        $project = Project::factory()->create();
        $employee = assignmentUser('employee');
        $employee->update(['bench_status' => BenchStatus::Assigned]);

        $assignment = ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employee->id,
            'completion_status' => 'pending',
        ]);

        $this->actingAs($hr)
            ->delete(route('projects.assignments.destroy', [$project, $assignment]));

        expect($employee->fresh()->bench_status)->toBe(BenchStatus::OnBench);
    });

    test('retains bench_status as assigned when employee still has other pending assignments', function () {
        $hr = assignmentUser('hr');
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();
        $employee = assignmentUser('employee');
        $employee->update(['bench_status' => BenchStatus::Assigned]);

        $assignment = ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employee->id,
            'completion_status' => 'pending',
        ]);

        // Another pending assignment on a different project
        ProjectAssignment::factory()->create([
            'project_id' => $otherProject->id,
            'user_id' => $employee->id,
            'completion_status' => 'pending',
        ]);

        $this->actingAs($hr)
            ->delete(route('projects.assignments.destroy', [$project, $assignment]));

        expect($employee->fresh()->bench_status)->toBe(BenchStatus::Assigned);
    });

    test('returns 403 for employee role', function () {
        $employee = assignmentUser('employee');
        $project = Project::factory()->create();
        $otherEmployee = assignmentUser('employee');
        $assignment = ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $otherEmployee->id,
        ]);

        $this->actingAs($employee)
            ->delete(route('projects.assignments.destroy', [$project, $assignment]))
            ->assertForbidden();
    });
});
