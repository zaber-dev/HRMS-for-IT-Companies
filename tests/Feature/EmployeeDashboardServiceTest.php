<?php

use App\Enums\BenchStatus;
use App\Enums\CompletionStatus;
use App\Models\LeaveRequest;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Skill;
use App\Models\SkillAssignment;
use App\Models\SkillCategory;
use App\Models\User;
use App\Services\Dashboard\EmployeeDashboardService;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->service = app(EmployeeDashboardService::class);
});

function makeEmpUser(array $attrs = []): User
{
    return User::factory()->create(array_merge([
        'is_active' => true,
        'must_change_password' => false,
        'bench_status' => BenchStatus::OnBench,
    ], $attrs))->assignRole('employee');
}

// ---------------------------------------------------------------------------
// personal_stats
// ---------------------------------------------------------------------------

test('personal_stats contains correct name and role', function () {
    $employee = makeEmpUser(['name' => 'Jane Doe']);

    $data = $this->service->get($employee);

    expect($data['personal_stats']['name'])->toBe('Jane Doe');
    expect($data['personal_stats']['role'])->toBe('employee');
});

test('personal_stats bench_status reflects the user bench_status', function () {
    $employee = makeEmpUser(['bench_status' => BenchStatus::Assigned]);

    $data = $this->service->get($employee);

    expect($data['personal_stats']['bench_status'])->toBe('assigned');
});

test('personal_stats skills_count reflects assigned skills', function () {
    $employee = makeEmpUser();
    SkillAssignment::factory()->count(3)->create(['user_id' => $employee->id]);

    $data = $this->service->get($employee);

    expect($data['personal_stats']['skills_count'])->toBe(3);
});

// ---------------------------------------------------------------------------
// my_tasks — sorting
// ---------------------------------------------------------------------------

test('my_tasks sorts overdue tasks first (most overdue first)', function () {
    $employee = makeEmpUser();
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();
    $project3 = Project::factory()->create();

    // Overdue by 10 days
    ProjectAssignment::factory()->create([
        'user_id' => $employee->id,
        'project_id' => $project1->id,
        'task_deadline' => now()->subDays(10)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    // Overdue by 2 days
    ProjectAssignment::factory()->create([
        'user_id' => $employee->id,
        'project_id' => $project2->id,
        'task_deadline' => now()->subDays(2)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    // Future — 5 days remaining
    ProjectAssignment::factory()->create([
        'user_id' => $employee->id,
        'project_id' => $project3->id,
        'task_deadline' => now()->addDays(5)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    $data = $this->service->get($employee);
    $tasks = $data['my_tasks'];

    expect($tasks[0]['days_remaining'])->toBe(-10);
    expect($tasks[1]['days_remaining'])->toBe(-2);
    expect($tasks[2]['days_remaining'])->toBe(5);
});

test('my_tasks only includes pending assignments', function () {
    $employee = makeEmpUser();
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();

    ProjectAssignment::factory()->create([
        'user_id' => $employee->id,
        'project_id' => $project1->id,
        'task_deadline' => now()->addDays(5)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    ProjectAssignment::factory()->complete()->create([
        'user_id' => $employee->id,
        'project_id' => $project2->id,
        'task_deadline' => now()->addDays(5)->toDateString(),
    ]);

    $data = $this->service->get($employee);

    expect($data['my_tasks'])->toHaveCount(1);
    expect($data['my_tasks'][0]['project_id'])->toBe($project1->id);
});

test('my_tasks only contains data for the authenticated employee', function () {
    $employeeA = makeEmpUser();
    $employeeB = makeEmpUser();
    $project = Project::factory()->create();

    ProjectAssignment::factory()->create([
        'user_id' => $employeeB->id,
        'project_id' => $project->id,
        'task_deadline' => now()->addDays(5)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    $data = $this->service->get($employeeA);

    expect($data['my_tasks'])->toBeEmpty();
});

// ---------------------------------------------------------------------------
// my_projects — is_near_deadline
// ---------------------------------------------------------------------------

test('my_projects sets is_near_deadline true for projects within 7 days', function () {
    $employee = makeEmpUser();
    $project = Project::factory()->inProgress()->create([
        'deadline' => now()->addDays(3)->toDateString(),
    ]);

    ProjectAssignment::factory()->create([
        'user_id' => $employee->id,
        'project_id' => $project->id,
        'task_deadline' => now()->addDays(30)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    $data = $this->service->get($employee);

    $entry = collect($data['my_projects'])->firstWhere('project_id', $project->id);
    expect($entry['is_near_deadline'])->toBeTrue();
});

test('my_projects sets is_near_deadline false for projects beyond 7 days', function () {
    $employee = makeEmpUser();
    $project = Project::factory()->inProgress()->create([
        'deadline' => now()->addDays(10)->toDateString(),
    ]);

    ProjectAssignment::factory()->create([
        'user_id' => $employee->id,
        'project_id' => $project->id,
        'task_deadline' => now()->addDays(30)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    $data = $this->service->get($employee);

    $entry = collect($data['my_projects'])->firstWhere('project_id', $project->id);
    expect($entry['is_near_deadline'])->toBeFalse();
});

test('my_projects sets is_near_deadline false for completed projects even within 7 days', function () {
    $employee = makeEmpUser();
    $project = Project::factory()->completed()->create([
        'deadline' => now()->addDays(2)->toDateString(),
    ]);

    ProjectAssignment::factory()->create([
        'user_id' => $employee->id,
        'project_id' => $project->id,
        'task_deadline' => now()->addDays(30)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    $data = $this->service->get($employee);

    $entry = collect($data['my_projects'])->firstWhere('project_id', $project->id);
    expect($entry['is_near_deadline'])->toBeFalse();
});

// ---------------------------------------------------------------------------
// my_leave
// ---------------------------------------------------------------------------

test('my_leave approved_days counts only current year approved requests', function () {
    $employee = makeEmpUser();

    // Current year — 10 days
    LeaveRequest::factory()->approved()->create([
        'user_id' => $employee->id,
        'start_date' => now()->startOfYear()->addDays(1)->toDateString(),
        'end_date' => now()->startOfYear()->addDays(10)->toDateString(),
        'submitted_at' => now(),
    ]);

    // Previous year — should not count
    LeaveRequest::factory()->approved()->create([
        'user_id' => $employee->id,
        'start_date' => now()->subYear()->startOfYear()->addDays(1)->toDateString(),
        'end_date' => now()->subYear()->startOfYear()->addDays(10)->toDateString(),
        'submitted_at' => now()->subYear(),
    ]);

    $data = $this->service->get($employee);

    expect($data['my_leave']['approved_days'])->toBe(10);
});

test('my_leave pending_count counts pending requests across all pending statuses', function () {
    $employee = makeEmpUser();

    LeaveRequest::factory()->pendingHr()->create(['user_id' => $employee->id, 'submitted_at' => now()]);
    LeaveRequest::factory()->pendingAdmin()->create(['user_id' => $employee->id, 'submitted_at' => now()]);
    LeaveRequest::factory()->approved()->create(['user_id' => $employee->id, 'submitted_at' => now()]);

    $data = $this->service->get($employee);

    expect($data['my_leave']['pending_count'])->toBe(2);
});

test('my_leave most_recent is null when no leave requests exist', function () {
    $employee = makeEmpUser();

    $data = $this->service->get($employee);

    expect($data['my_leave']['most_recent'])->toBeNull();
});

test('my_leave only contains data for the authenticated employee', function () {
    $employeeA = makeEmpUser();
    $employeeB = makeEmpUser();

    LeaveRequest::factory()->approved()->create([
        'user_id' => $employeeB->id,
        'start_date' => now()->startOfYear()->addDays(1)->toDateString(),
        'end_date' => now()->startOfYear()->addDays(10)->toDateString(),
        'submitted_at' => now(),
    ]);

    $data = $this->service->get($employeeA);

    expect($data['my_leave']['approved_days'])->toBe(0);
    expect($data['my_leave']['pending_count'])->toBe(0);
    expect($data['my_leave']['most_recent'])->toBeNull();
});

// ---------------------------------------------------------------------------
// my_skills — grouping
// ---------------------------------------------------------------------------

test('my_skills groups skills by category name', function () {
    $employee = makeEmpUser();
    $catA = SkillCategory::factory()->create(['name' => 'Backend']);
    $catB = SkillCategory::factory()->create(['name' => 'Frontend']);

    $skillA = Skill::factory()->withCategory($catA)->create(['name' => 'PHP']);
    $skillB = Skill::factory()->withCategory($catB)->create(['name' => 'React']);

    SkillAssignment::factory()->create(['user_id' => $employee->id, 'skill_id' => $skillA->id]);
    SkillAssignment::factory()->create(['user_id' => $employee->id, 'skill_id' => $skillB->id]);

    $data = $this->service->get($employee);

    expect($data['my_skills'])->toHaveKeys(['Backend', 'Frontend']);
    expect($data['my_skills']['Backend'][0]['name'])->toBe('PHP');
    expect($data['my_skills']['Frontend'][0]['name'])->toBe('React');
});

test('my_skills only contains data for the authenticated employee', function () {
    $employeeA = makeEmpUser();
    $employeeB = makeEmpUser();

    $skill = Skill::factory()->create();
    SkillAssignment::factory()->create(['user_id' => $employeeB->id, 'skill_id' => $skill->id]);

    $data = $this->service->get($employeeA);

    expect($data['my_skills'])->toBeEmpty();
});
