<?php

use App\Enums\CompletionStatus;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\User;
use App\Services\Dashboard\DeadlineAlertService;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->service = app(DeadlineAlertService::class);
});

function makeDeadlineEmployee(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
        'bench_status' => 'on_bench',
    ])->assignRole('employee');
}

// ---------------------------------------------------------------------------
// Inclusion criteria
// ---------------------------------------------------------------------------

test('overdue pending assignment is included', function () {
    $employee = makeDeadlineEmployee();
    $project = Project::factory()->create();

    ProjectAssignment::factory()->create([
        'user_id' => $employee->id,
        'project_id' => $project->id,
        'task_deadline' => now()->subDays(3)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    $results = $this->service->get();

    expect($results)->toHaveCount(1);
    expect($results[0]['user_id'])->toBe($employee->id);
    expect($results[0]['days_overdue'])->toBe(3);
});

// ---------------------------------------------------------------------------
// Exclusion criteria
// ---------------------------------------------------------------------------

test('completed assignment is excluded even if overdue', function () {
    $employee = makeDeadlineEmployee();
    $project = Project::factory()->create();

    ProjectAssignment::factory()->complete()->create([
        'user_id' => $employee->id,
        'project_id' => $project->id,
        'task_deadline' => now()->subDays(5)->toDateString(),
    ]);

    $results = $this->service->get();

    expect($results)->toBeEmpty();
});

test('future deadline pending assignment is excluded', function () {
    $employee = makeDeadlineEmployee();
    $project = Project::factory()->create();

    ProjectAssignment::factory()->create([
        'user_id' => $employee->id,
        'project_id' => $project->id,
        'task_deadline' => now()->addDays(5)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    $results = $this->service->get();

    expect($results)->toBeEmpty();
});

test('HR users are excluded from results', function () {
    $hr = User::factory()->create(['is_active' => true, 'must_change_password' => false])->assignRole('hr');
    $project = Project::factory()->create();

    ProjectAssignment::factory()->create([
        'user_id' => $hr->id,
        'project_id' => $project->id,
        'task_deadline' => now()->subDays(5)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    $results = $this->service->get();

    $userIds = array_column($results, 'user_id');
    expect($userIds)->not->toContain($hr->id);
});

// ---------------------------------------------------------------------------
// Multiple assignments per employee
// ---------------------------------------------------------------------------

test('multiple overdue assignments for one employee produce separate entries', function () {
    $employee = makeDeadlineEmployee();
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();

    ProjectAssignment::factory()->create([
        'user_id' => $employee->id,
        'project_id' => $project1->id,
        'task_deadline' => now()->subDays(2)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    ProjectAssignment::factory()->create([
        'user_id' => $employee->id,
        'project_id' => $project2->id,
        'task_deadline' => now()->subDays(5)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    $results = $this->service->get();

    expect($results)->toHaveCount(2);
});

// ---------------------------------------------------------------------------
// Sorting
// ---------------------------------------------------------------------------

test('results are sorted descending by days_overdue', function () {
    $employee = makeDeadlineEmployee();
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();

    ProjectAssignment::factory()->create([
        'user_id' => $employee->id,
        'project_id' => $project1->id,
        'task_deadline' => now()->subDays(2)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    ProjectAssignment::factory()->create([
        'user_id' => $employee->id,
        'project_id' => $project2->id,
        'task_deadline' => now()->subDays(10)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    $results = $this->service->get();

    expect($results[0]['days_overdue'])->toBeGreaterThan($results[1]['days_overdue']);
    expect($results[0]['project_id'])->toBe($project2->id);
});
