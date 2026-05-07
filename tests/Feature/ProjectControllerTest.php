<?php

use App\Enums\BenchStatus;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Skill;
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
function projectUser(string $role): User
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
    test('returns 200 for hr role', function () {
        $hr = projectUser('hr');

        $this->withoutVite()
            ->actingAs($hr)
            ->get(route('projects.index'))
            ->assertOk();
    });

    test('returns 200 for admin role', function () {
        $admin = projectUser('admin');

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('projects.index'))
            ->assertOk();
    });

    test('returns 200 for super_admin role', function () {
        $superAdmin = projectUser('super_admin');

        $this->withoutVite()
            ->actingAs($superAdmin)
            ->get(route('projects.index'))
            ->assertOk();
    });

    test('returns 403 for employee role', function () {
        $employee = projectUser('employee');

        $this->actingAs($employee)
            ->get(route('projects.index'))
            ->assertForbidden();
    });
});

// ---------------------------------------------------------------------------
// create
// ---------------------------------------------------------------------------

describe('create', function () {
    test('returns 200 for hr role', function () {
        $hr = projectUser('hr');

        $this->withoutVite()
            ->actingAs($hr)
            ->get(route('projects.create'))
            ->assertOk();
    });

    test('returns 200 for admin role', function () {
        $admin = projectUser('admin');

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('projects.create'))
            ->assertOk();
    });

    test('returns 200 for super_admin role', function () {
        $superAdmin = projectUser('super_admin');

        $this->withoutVite()
            ->actingAs($superAdmin)
            ->get(route('projects.create'))
            ->assertOk();
    });
});

// ---------------------------------------------------------------------------
// store — happy path
// ---------------------------------------------------------------------------

describe('store (happy path)', function () {
    test('HR creates project and redirects to show', function () {
        $hr = projectUser('hr');

        $response = $this->actingAs($hr)
            ->post(route('projects.store'), [
                'name' => 'Alpha Project',
                'description' => 'A test project',
                'features_list' => null,
                'status' => 'planning',
                'deadline' => now()->addDays(30)->toDateString(),
            ]);

        $project = Project::where('name', 'Alpha Project')->firstOrFail();

        $response->assertRedirect(route('projects.show', $project));
        $this->assertDatabaseHas('projects', ['name' => 'Alpha Project']);
    });

    test('Admin creates project and redirects to show', function () {
        $admin = projectUser('admin');

        $response = $this->actingAs($admin)
            ->post(route('projects.store'), [
                'name' => 'Beta Project',
                'status' => 'in_progress',
                'deadline' => now()->addDays(60)->toDateString(),
            ]);

        $project = Project::where('name', 'Beta Project')->firstOrFail();

        $response->assertRedirect(route('projects.show', $project));
    });

    test('super_admin creates project with skills and redirects to show', function () {
        $superAdmin = projectUser('super_admin');
        $skill = Skill::factory()->create(['is_active' => true]);

        $response = $this->actingAs($superAdmin)
            ->post(route('projects.store'), [
                'name' => 'Gamma Project',
                'status' => 'planning',
                'deadline' => now()->addDays(45)->toDateString(),
                'skill_ids' => [$skill->id],
            ]);

        $project = Project::where('name', 'Gamma Project')->firstOrFail();

        $response->assertRedirect(route('projects.show', $project));
        expect($project->skills()->count())->toBe(1);
    });
});

// ---------------------------------------------------------------------------
// store — validation errors
// ---------------------------------------------------------------------------

describe('store — validation: duplicate name', function () {
    test('returns 422 for duplicate name', function () {
        $hr = projectUser('hr');
        Project::factory()->create(['name' => 'Existing Project']);

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('projects.store'), [
                'name' => 'Existing Project',
                'status' => 'planning',
                'deadline' => now()->addDays(30)->toDateString(),
            ])
            ->assertStatus(422)
            ->assertInvalid(['name']);
    });
});

describe('store — validation: name length', function () {
    test('returns 422 for name shorter than 2 characters', function () {
        $hr = projectUser('hr');

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('projects.store'), [
                'name' => 'A',
                'status' => 'planning',
                'deadline' => now()->addDays(30)->toDateString(),
            ])
            ->assertStatus(422)
            ->assertInvalid(['name']);
    });

    test('returns 422 for name longer than 150 characters', function () {
        $hr = projectUser('hr');

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('projects.store'), [
                'name' => str_repeat('a', 151),
                'status' => 'planning',
                'deadline' => now()->addDays(30)->toDateString(),
            ])
            ->assertStatus(422)
            ->assertInvalid(['name']);
    });

    test('accepts name of exactly 2 characters', function () {
        $hr = projectUser('hr');

        $response = $this->actingAs($hr)
            ->post(route('projects.store'), [
                'name' => 'AB',
                'status' => 'planning',
                'deadline' => now()->addDays(30)->toDateString(),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', ['name' => 'AB']);
    });

    test('accepts name of exactly 150 characters', function () {
        $hr = projectUser('hr');
        $name = str_repeat('a', 150);

        $response = $this->actingAs($hr)
            ->post(route('projects.store'), [
                'name' => $name,
                'status' => 'planning',
                'deadline' => now()->addDays(30)->toDateString(),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', ['name' => $name]);
    });
});

describe('store — validation: invalid status', function () {
    test('returns 422 for invalid status value', function () {
        $hr = projectUser('hr');

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('projects.store'), [
                'name' => 'Status Test Project',
                'status' => 'invalid_status',
                'deadline' => now()->addDays(30)->toDateString(),
            ])
            ->assertStatus(422)
            ->assertInvalid(['status']);
    });
});

describe('store — validation: past deadline', function () {
    test('returns 422 for past deadline', function () {
        $hr = projectUser('hr');

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('projects.store'), [
                'name' => 'Past Deadline Project',
                'status' => 'planning',
                'deadline' => now()->subDay()->toDateString(),
            ])
            ->assertStatus(422)
            ->assertInvalid(['deadline']);
    });

    test('accepts today as deadline', function () {
        $hr = projectUser('hr');

        $response = $this->actingAs($hr)
            ->post(route('projects.store'), [
                'name' => 'Today Deadline Project',
                'status' => 'planning',
                'deadline' => now()->toDateString(),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', ['name' => 'Today Deadline Project']);
    });
});

describe('store — access control', function () {
    test('returns 403 for employee role', function () {
        $employee = projectUser('employee');

        $this->actingAs($employee)
            ->post(route('projects.store'), [
                'name' => 'Forbidden Project',
                'status' => 'planning',
                'deadline' => now()->addDays(30)->toDateString(),
            ])
            ->assertForbidden();
    });
});

// ---------------------------------------------------------------------------
// show
// ---------------------------------------------------------------------------

describe('show', function () {
    test('returns 200 for hr role', function () {
        $hr = projectUser('hr');
        $project = Project::factory()->create();

        $this->withoutVite()
            ->actingAs($hr)
            ->get(route('projects.show', $project))
            ->assertOk();
    });

    test('returns 200 for admin role', function () {
        $admin = projectUser('admin');
        $project = Project::factory()->create();

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('projects.show', $project))
            ->assertOk();
    });

    test('returns 200 for super_admin role', function () {
        $superAdmin = projectUser('super_admin');
        $project = Project::factory()->create();

        $this->withoutVite()
            ->actingAs($superAdmin)
            ->get(route('projects.show', $project))
            ->assertOk();
    });

    test('returns 200 for employee assigned to the project', function () {
        $employee = projectUser('employee');
        $project = Project::factory()->create();

        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employee->id,
        ]);

        $this->withoutVite()
            ->actingAs($employee)
            ->get(route('projects.show', $project))
            ->assertOk();
    });

    test('returns 403 for employee not assigned to the project', function () {
        $employee = projectUser('employee');
        $project = Project::factory()->create();

        $this->actingAs($employee)
            ->get(route('projects.show', $project))
            ->assertForbidden();
    });
});

// ---------------------------------------------------------------------------
// edit
// ---------------------------------------------------------------------------

describe('edit', function () {
    test('returns 200 for hr role', function () {
        $hr = projectUser('hr');
        $project = Project::factory()->create();

        $this->withoutVite()
            ->actingAs($hr)
            ->get(route('projects.edit', $project))
            ->assertOk();
    });

    test('returns 200 for admin role', function () {
        $admin = projectUser('admin');
        $project = Project::factory()->create();

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('projects.edit', $project))
            ->assertOk();
    });

    test('returns 200 for super_admin role', function () {
        $superAdmin = projectUser('super_admin');
        $project = Project::factory()->create();

        $this->withoutVite()
            ->actingAs($superAdmin)
            ->get(route('projects.edit', $project))
            ->assertOk();
    });
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

describe('update (happy path)', function () {
    test('HR updates project and redirects to show', function () {
        $hr = projectUser('hr');
        $project = Project::factory()->create(['name' => 'Original Name']);

        $response = $this->actingAs($hr)
            ->put(route('projects.update', $project), [
                'name' => 'Updated Name',
                'description' => 'Updated description',
                'features_list' => null,
                'status' => 'in_progress',
                'deadline' => now()->addDays(60)->toDateString(),
            ]);

        $response->assertRedirect(route('projects.show', $project));

        $project->refresh();
        expect($project->name)->toBe('Updated Name');
        expect($project->status->value)->toBe('in_progress');
    });

    test('updating a project with its own name does not return a duplicate error', function () {
        $hr = projectUser('hr');
        $project = Project::factory()->create(['name' => 'My Project']);

        $this->actingAs($hr)
            ->put(route('projects.update', $project), [
                'name' => 'My Project',
                'status' => 'planning',
                'deadline' => now()->addDays(30)->toDateString(),
            ])
            ->assertRedirect(route('projects.show', $project));
    });
});

describe('update — validation: duplicate name', function () {
    test('returns 422 for duplicate name on another project', function () {
        $hr = projectUser('hr');
        Project::factory()->create(['name' => 'Taken Name']);
        $project = Project::factory()->create(['name' => 'My Project']);

        $this->actingAs($hr)
            ->withHeaders(['Accept' => 'application/json'])
            ->put(route('projects.update', $project), [
                'name' => 'Taken Name',
                'status' => 'planning',
                'deadline' => now()->addDays(30)->toDateString(),
            ])
            ->assertStatus(422)
            ->assertInvalid(['name']);
    });
});

// ---------------------------------------------------------------------------
// delete
// ---------------------------------------------------------------------------

describe('delete', function () {
    test('returns 200 for hr role', function () {
        $hr = projectUser('hr');
        $project = Project::factory()->create();

        $this->withoutVite()
            ->actingAs($hr)
            ->get(route('projects.delete', $project))
            ->assertOk();
    });

    test('returns 200 for admin role', function () {
        $admin = projectUser('admin');
        $project = Project::factory()->create();

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('projects.delete', $project))
            ->assertOk();
    });

    test('returns 200 for super_admin role', function () {
        $superAdmin = projectUser('super_admin');
        $project = Project::factory()->create();

        $this->withoutVite()
            ->actingAs($superAdmin)
            ->get(route('projects.delete', $project))
            ->assertOk();
    });
});

// ---------------------------------------------------------------------------
// destroy
// ---------------------------------------------------------------------------

describe('destroy', function () {
    test('deletes project, cascades assignments, recalculates bench status, redirects to index', function () {
        $hr = projectUser('hr');
        $project = Project::factory()->create();

        // Employee with only this assignment — should revert to on_bench
        $employeeOnlyAssignment = projectUser('employee');
        $employeeOnlyAssignment->bench_status = BenchStatus::Assigned;
        $employeeOnlyAssignment->save();

        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employeeOnlyAssignment->id,
            'completion_status' => 'pending',
        ]);

        // Employee with another active assignment on a different project — should stay assigned
        $employeeOtherAssignment = projectUser('employee');
        $employeeOtherAssignment->bench_status = BenchStatus::Assigned;
        $employeeOtherAssignment->save();

        $otherProject = Project::factory()->create();

        ProjectAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $employeeOtherAssignment->id,
            'completion_status' => 'pending',
        ]);

        ProjectAssignment::factory()->create([
            'project_id' => $otherProject->id,
            'user_id' => $employeeOtherAssignment->id,
            'completion_status' => 'pending',
        ]);

        $this->actingAs($hr)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'));

        // Project is deleted
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);

        // Assignments for the deleted project are cascaded
        $this->assertDatabaseMissing('project_assignments', ['project_id' => $project->id]);

        // Employee with no remaining assignments is now on_bench
        expect($employeeOnlyAssignment->fresh()->bench_status)->toBe(BenchStatus::OnBench);

        // Employee with another pending assignment stays assigned
        expect($employeeOtherAssignment->fresh()->bench_status)->toBe(BenchStatus::Assigned);
    });

    test('returns 403 for employee role', function () {
        $employee = projectUser('employee');
        $project = Project::factory()->create();

        $this->actingAs($employee)
            ->delete(route('projects.destroy', $project))
            ->assertForbidden();
    });
});
