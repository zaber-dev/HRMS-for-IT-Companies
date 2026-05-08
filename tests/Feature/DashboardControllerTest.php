<?php

use App\Enums\BenchStatus;
use App\Enums\CompletionStatus;
use App\Models\LeaveRequest;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

function ctrlUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge([
        'is_active' => true,
        'must_change_password' => false,
        'bench_status' => BenchStatus::OnBench,
    ], $attrs))->assignRole($role);
}

// ---------------------------------------------------------------------------
// Happy paths — HR roles render dashboard/hr with correct eager props
// ---------------------------------------------------------------------------

test('HR user gets dashboard/hr with workforceSummary, projectHealth, leaveQueueCount', function () {
    $hr = ctrlUser('hr');

    $this->withoutVite()
        ->actingAs($hr)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/hr')
            ->has('workforceSummary')
            ->has('projectHealth')
            ->has('leaveQueueCount')
        );
});

test('Admin user gets dashboard/hr with admin-scoped leaveQueueCount', function () {
    $admin = ctrlUser('admin');
    $employee = ctrlUser('employee');

    LeaveRequest::factory()->pendingAdmin()->create([
        'user_id' => $employee->id,
        'submitted_at' => now(),
    ]);

    $this->withoutVite()
        ->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/hr')
            ->where('leaveQueueCount', 1)
        );
});

test('Super Admin user gets dashboard/hr with super_admin-scoped leaveQueueCount', function () {
    $superAdmin = ctrlUser('super_admin');
    $employee = ctrlUser('employee');

    LeaveRequest::factory()->pendingSuperAdmin()->create([
        'user_id' => $employee->id,
        'submitted_at' => now(),
    ]);

    $this->withoutVite()
        ->actingAs($superAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/hr')
            ->where('leaveQueueCount', 1)
        );
});

// ---------------------------------------------------------------------------
// Happy path — Employee renders dashboard/employee with all personal props
// ---------------------------------------------------------------------------

test('Employee user gets dashboard/employee with all personal props', function () {
    $employee = ctrlUser('employee');

    $this->withoutVite()
        ->actingAs($employee)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/employee')
            ->has('personalStats')
            ->has('myTasks')
            ->has('myProjects')
            ->has('myLeave')
            ->has('mySkills')
        );
});

// ---------------------------------------------------------------------------
// Deferred props on HR dashboard
// ---------------------------------------------------------------------------

test('ptoAlerts, deadlineAlerts, skillCoverageProjects are deferred props on HR dashboard', function () {
    $hr = ctrlUser('hr');

    $this->withoutVite()
        ->actingAs($hr)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/hr')
            ->missing('ptoAlerts')      // deferred — not in initial response
            ->missing('deadlineAlerts')
            ->missing('skillCoverageProjects')
            ->loadDeferredProps(fn ($reload) => $reload
                ->has('ptoAlerts')
                ->has('deadlineAlerts')
                ->has('skillCoverageProjects')
            )
        );
});

// ---------------------------------------------------------------------------
// Access control
// ---------------------------------------------------------------------------

test('unauthenticated user is redirected to login', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('inactive user is redirected to login', function () {
    $user = User::factory()->create([
        'is_active' => false,
        'must_change_password' => false,
        'bench_status' => BenchStatus::OnBench,
    ])->assignRole('employee');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('roleless user receives 403', function () {
    $user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Role isolation — employee response contains only that employee's own data
// ---------------------------------------------------------------------------

test('employee dashboard personalStats contains only the authenticated employee name', function () {
    $employeeA = ctrlUser('employee', ['name' => 'Alice Smith']);
    $employeeB = ctrlUser('employee', ['name' => 'Bob Jones']);

    // Give employee B a task so there is data in the system
    $project = Project::factory()->create();
    ProjectAssignment::factory()->create([
        'user_id' => $employeeB->id,
        'project_id' => $project->id,
        'task_deadline' => now()->addDays(5)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    $this->withoutVite()
        ->actingAs($employeeA)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/employee')
            ->where('personalStats.name', 'Alice Smith')
        );
});

test('employee dashboard myTasks contains only the authenticated employee tasks', function () {
    $employeeA = ctrlUser('employee');
    $employeeB = ctrlUser('employee');

    $project = Project::factory()->create();
    ProjectAssignment::factory()->create([
        'user_id' => $employeeB->id,
        'project_id' => $project->id,
        'task_deadline' => now()->addDays(5)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    $this->withoutVite()
        ->actingAs($employeeA)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/employee')
            ->where('myTasks', [])
        );
});

// ---------------------------------------------------------------------------
// HR dashboard does not expose employee-only props
// ---------------------------------------------------------------------------

test('HR dashboard response does not contain employee-only props', function () {
    $hr = ctrlUser('hr');

    $this->withoutVite()
        ->actingAs($hr)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/hr')
            ->missing('personalStats')
            ->missing('myTasks')
            ->missing('myProjects')
            ->missing('myLeave')
            ->missing('mySkills')
        );
});

// ---------------------------------------------------------------------------
// workforceSummary excludes non-employee roles
// ---------------------------------------------------------------------------

test('workforceSummary total_employees counts only employee-role users', function () {
    $hr = ctrlUser('hr');
    ctrlUser('admin');
    ctrlUser('super_admin');
    ctrlUser('employee');
    ctrlUser('employee');

    $this->withoutVite()
        ->actingAs($hr)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/hr')
            ->where('workforceSummary.total_employees', 2)
        );
});
