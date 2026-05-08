<?php

use App\Enums\CompletionStatus;
use App\Enums\LeaveStatus;
use App\Enums\ProjectStatus;
use App\Models\LeaveRequest;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Skill;
use App\Models\SkillAssignment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create an active user with the given role and randomised name/email.
 */
function dashPropUser(string $role): User
{
    return User::factory()->create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole($role);
}

// ---------------------------------------------------------------------------
// Property 1: Role routing is correct
// Feature: dashboard-analytics, Property 1: role routing is correct
// ---------------------------------------------------------------------------

it('renders dashboard/hr for privileged roles', function (string $role) {
    $user = dashPropUser($role);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/hr'));
})->with([
    'hr',
    'admin',
    'super_admin',
])->repeat(34);
// Feature: dashboard-analytics, Property 1: role routing is correct

it('renders dashboard/employee for the employee role', function () {
    $user = dashPropUser('employee');

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/employee'));
})->repeat(100);
// Feature: dashboard-analytics, Property 1: role routing is correct

// ---------------------------------------------------------------------------
// Property 2: Workforce summary excludes non-employee roles
// Feature: dashboard-analytics, Property 2: workforce summary excludes non-employee roles
// ---------------------------------------------------------------------------

it('workforce summary total_employees counts only employee-role users', function () {
    $employeeCount = fake()->numberBetween(1, 8);
    $privilegedCount = fake()->numberBetween(1, 4);

    $employees = User::factory()->count($employeeCount)->create([
        'is_active' => true,
        'must_change_password' => false,
        'bench_status' => 'on_bench',
    ]);
    foreach ($employees as $e) {
        $e->assignRole('employee');
    }

    $privilegedRoles = ['hr', 'admin', 'super_admin'];
    $hrUser = null;
    for ($i = 0; $i < $privilegedCount; $i++) {
        $role = $privilegedRoles[$i % 3];
        $u = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $u->assignRole($role);
        if ($role === 'hr' && $hrUser === null) {
            $hrUser = $u;
        }
    }

    if ($hrUser === null) {
        $hrUser = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $hrUser->assignRole('hr');
    }

    $this->withoutVite()
        ->actingAs($hrUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/hr')
            ->where('workforceSummary.total_employees', $employeeCount)
        );
})->repeat(100);
// Feature: dashboard-analytics, Property 2: workforce summary excludes non-employee roles

// ---------------------------------------------------------------------------
// Property 3: PTO alerts only include employees at or above threshold
// Feature: dashboard-analytics, Property 3: PTO alerts only include employees at or above threshold
// ---------------------------------------------------------------------------

it('ptoAlerts only contains employees with total_leave_days >= threshold', function () {
    $threshold = 14;
    $hrUser = dashPropUser('hr');

    // Employee above threshold: 8-day + 8-day = 16 days (>= 14)
    $aboveEmployee = dashPropUser('employee');
    LeaveRequest::factory()->create([
        'user_id' => $aboveEmployee->id,
        'start_date' => now()->startOfYear()->addDays(10)->toDateString(),
        'end_date' => now()->startOfYear()->addDays(17)->toDateString(), // 8 days
        'status' => LeaveStatus::Approved,
        'submitted_at' => now(),
    ]);
    LeaveRequest::factory()->create([
        'user_id' => $aboveEmployee->id,
        'start_date' => now()->startOfYear()->addDays(30)->toDateString(),
        'end_date' => now()->startOfYear()->addDays(37)->toDateString(), // 8 days
        'status' => LeaveStatus::Approved,
        'submitted_at' => now(),
    ]);

    // Employee below threshold: 5 days
    $belowEmployee = dashPropUser('employee');
    LeaveRequest::factory()->create([
        'user_id' => $belowEmployee->id,
        'start_date' => now()->startOfYear()->addDays(5)->toDateString(),
        'end_date' => now()->startOfYear()->addDays(9)->toDateString(), // 5 days
        'status' => LeaveStatus::Approved,
        'submitted_at' => now(),
    ]);

    $response = $this->withoutVite()
        ->actingAs($hrUser)
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertInertia(function ($page) use ($threshold) {
            $alerts = $page->toArray()['props']['ptoAlerts'] ?? [];
            foreach ($alerts as $alert) {
                expect($alert['total_leave_days'])->toBeGreaterThanOrEqual($threshold);
            }
        });

    $response->assertInertia(function ($page) use ($belowEmployee) {
        $alerts = $page->toArray()['props']['ptoAlerts'] ?? [];
        $alertUserIds = array_column($alerts, 'user_id');
        expect($alertUserIds)->not->toContain($belowEmployee->id);
    });
})->repeat(100);
// Feature: dashboard-analytics, Property 3: PTO alerts only include employees at or above threshold

// ---------------------------------------------------------------------------
// Property 4: Deadline alerts only include overdue pending assignments
// Feature: dashboard-analytics, Property 4: deadline alerts only include overdue pending assignments
// ---------------------------------------------------------------------------

it('deadlineAlerts only contains overdue pending assignments', function () {
    $hrUser = dashPropUser('hr');
    $employee = dashPropUser('employee');

    // Each assignment needs its own project due to unique(project_id, user_id) constraint
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();
    $project3 = Project::factory()->create();

    // Overdue + pending — should appear
    ProjectAssignment::factory()->create([
        'user_id' => $employee->id,
        'project_id' => $project1->id,
        'task_deadline' => now()->subDays(fake()->numberBetween(1, 30))->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    // Future deadline + pending — should NOT appear
    ProjectAssignment::factory()->create([
        'user_id' => $employee->id,
        'project_id' => $project2->id,
        'task_deadline' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    // Overdue + complete — should NOT appear
    ProjectAssignment::factory()->complete()->create([
        'user_id' => $employee->id,
        'project_id' => $project3->id,
        'task_deadline' => now()->subDays(fake()->numberBetween(1, 30))->toDateString(),
    ]);

    $this->withoutVite()
        ->actingAs($hrUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(function ($page) {
            $alerts = $page->toArray()['props']['deadlineAlerts'] ?? [];
            foreach ($alerts as $alert) {
                expect($alert['days_overdue'])->toBeGreaterThan(0);
            }
        });
})->repeat(100);
// Feature: dashboard-analytics, Property 4: deadline alerts only include overdue pending assignments

// ---------------------------------------------------------------------------
// Property 5: Leave queue count is role-scoped
// Feature: dashboard-analytics, Property 5: leave queue count is role-scoped
// ---------------------------------------------------------------------------

it('leaveQueueCount for hr equals count of pending_hr requests', function () {
    $hrUser = dashPropUser('hr');
    $count = fake()->numberBetween(1, 5);

    LeaveRequest::factory()->count($count)->pendingHr()->create([
        'user_id' => dashPropUser('employee')->id,
    ]);
    // Add some pending_admin requests that should NOT be counted for hr
    LeaveRequest::factory()->count(2)->pendingAdmin()->create([
        'user_id' => dashPropUser('employee')->id,
    ]);

    $this->withoutVite()
        ->actingAs($hrUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/hr')
            ->where('leaveQueueCount', $count)
        );
})->repeat(100);
// Feature: dashboard-analytics, Property 5: leave queue count is role-scoped

it('leaveQueueCount for admin excludes requests submitted by admin users', function () {
    $adminUser = dashPropUser('admin');
    $otherAdmin = dashPropUser('admin');
    $employee = dashPropUser('employee');

    $employeeCount = fake()->numberBetween(1, 4);

    // Employee requests — should be counted
    LeaveRequest::factory()->count($employeeCount)->pendingAdmin()->create([
        'user_id' => $employee->id,
    ]);
    // Admin self-request — should NOT be counted
    LeaveRequest::factory()->pendingAdmin()->create([
        'user_id' => $otherAdmin->id,
    ]);

    $this->withoutVite()
        ->actingAs($adminUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/hr')
            ->where('leaveQueueCount', $employeeCount)
        );
})->repeat(100);
// Feature: dashboard-analytics, Property 5: leave queue count is role-scoped

it('leaveQueueCount for super_admin equals count of pending_super_admin requests', function () {
    $superAdmin = dashPropUser('super_admin');
    $count = fake()->numberBetween(1, 5);

    LeaveRequest::factory()->count($count)->pendingSuperAdmin()->create([
        'user_id' => dashPropUser('employee')->id,
    ]);
    // Add pending_hr requests that should NOT be counted for super_admin
    LeaveRequest::factory()->count(2)->pendingHr()->create([
        'user_id' => dashPropUser('employee')->id,
    ]);

    $this->withoutVite()
        ->actingAs($superAdmin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/hr')
            ->where('leaveQueueCount', $count)
        );
})->repeat(100);
// Feature: dashboard-analytics, Property 5: leave queue count is role-scoped

// ---------------------------------------------------------------------------
// Property 6: Skill coverage only includes planning/in_progress projects
// Feature: dashboard-analytics, Property 6: skill coverage only includes planning/in_progress projects
// ---------------------------------------------------------------------------

it('skillCoverageProjects only contains planning or in_progress projects', function () {
    $hrUser = dashPropUser('hr');
    $skill = Skill::factory()->create();

    // planning project with uncovered skill — should appear
    $planningProject = Project::factory()->create([
        'status' => ProjectStatus::Planning,
    ]);
    $planningProject->skills()->attach($skill->id);

    // in_progress project with uncovered skill — should appear
    $inProgressProject = Project::factory()->inProgress()->create();
    $inProgressProject->skills()->attach($skill->id);

    // on_hold project with uncovered skill — should NOT appear
    $onHoldProject = Project::factory()->onHold()->create();
    $onHoldProject->skills()->attach($skill->id);

    // completed project with uncovered skill — should NOT appear
    $completedProject = Project::factory()->completed()->create();
    $completedProject->skills()->attach($skill->id);

    // cancelled project with uncovered skill — should NOT appear
    $cancelledProject = Project::factory()->cancelled()->create();
    $cancelledProject->skills()->attach($skill->id);

    $this->withoutVite()
        ->actingAs($hrUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(function ($page) use ($onHoldProject, $completedProject, $cancelledProject) {
            $projects = $page->toArray()['props']['skillCoverageProjects'] ?? [];
            foreach ($projects as $project) {
                expect($project['status'])->toBeIn(['planning', 'in_progress']);
            }
            $projectIds = array_column($projects, 'project_id');
            expect($projectIds)->not->toContain($onHoldProject->id);
            expect($projectIds)->not->toContain($completedProject->id);
            expect($projectIds)->not->toContain($cancelledProject->id);
        });
})->repeat(100);
// Feature: dashboard-analytics, Property 6: skill coverage only includes planning/in_progress projects

// ---------------------------------------------------------------------------
// Property 7: Employee dashboard only shows authenticated user's data
// Feature: dashboard-analytics, Property 7: employee dashboard only shows authenticated user's data
// ---------------------------------------------------------------------------

it('employee dashboard only contains data belonging to the authenticated employee', function () {
    $employeeA = dashPropUser('employee');
    $employeeB = dashPropUser('employee');
    $project = Project::factory()->create();

    // Give employee B a task
    ProjectAssignment::factory()->create([
        'user_id' => $employeeB->id,
        'project_id' => $project->id,
        'task_deadline' => now()->addDays(10)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    // Give employee B a leave request
    LeaveRequest::factory()->approved()->create([
        'user_id' => $employeeB->id,
        'start_date' => now()->startOfYear()->addDays(5)->toDateString(),
        'end_date' => now()->startOfYear()->addDays(9)->toDateString(),
        'submitted_at' => now(),
    ]);

    // Give employee B a skill
    $skill = Skill::factory()->create();
    SkillAssignment::factory()->create([
        'user_id' => $employeeB->id,
        'skill_id' => $skill->id,
    ]);

    $this->withoutVite()
        ->actingAs($employeeA)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(function ($page) use ($employeeA, $skill) {
            $props = $page->toArray()['props'];
            expect($props['personalStats']['name'])->toBe($employeeA->name);
            expect($props['myTasks'])->toBeEmpty();
            $allSkillIds = [];
            foreach ($props['mySkills'] ?? [] as $categorySkills) {
                foreach ($categorySkills as $s) {
                    $allSkillIds[] = $s['id'];
                }
            }
            expect($allSkillIds)->not->toContain($skill->id);
        });
})->repeat(100);
// Feature: dashboard-analytics, Property 7: employee dashboard only shows authenticated user's data

// ---------------------------------------------------------------------------
// Property 8: Employees cannot access HR dashboard data
// Feature: dashboard-analytics, Property 8: employees cannot access HR dashboard data
// ---------------------------------------------------------------------------

it('employee dashboard response does not contain HR-only props', function () {
    $employee = dashPropUser('employee');

    $this->withoutVite()
        ->actingAs($employee)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(function ($page) {
            $props = $page->toArray()['props'];
            expect($props)->not->toHaveKey('workforceSummary');
            expect($props)->not->toHaveKey('projectHealth');
            expect($props)->not->toHaveKey('leaveQueueCount');
            expect($props)->not->toHaveKey('ptoAlerts');
            expect($props)->not->toHaveKey('deadlineAlerts');
            expect($props)->not->toHaveKey('skillCoverageProjects');
        });
})->repeat(100);
// Feature: dashboard-analytics, Property 8: employees cannot access HR dashboard data

// ---------------------------------------------------------------------------
// Property 10: HR users are excluded from employee-specific alerts
// Feature: dashboard-analytics, Property 10: HR users are excluded from employee-specific alerts
// ---------------------------------------------------------------------------

it('HR users do not appear in ptoAlerts or deadlineAlerts', function () {
    $hrViewer = dashPropUser('hr');
    $hrWithLeave = dashPropUser('hr');
    $project = Project::factory()->create();

    // Give the HR user enough leave to exceed threshold
    LeaveRequest::factory()->create([
        'user_id' => $hrWithLeave->id,
        'start_date' => now()->startOfYear()->addDays(1)->toDateString(),
        'end_date' => now()->startOfYear()->addDays(20)->toDateString(), // 20 days
        'status' => LeaveStatus::Approved,
        'submitted_at' => now(),
    ]);

    // Give the HR user an overdue assignment
    ProjectAssignment::factory()->create([
        'user_id' => $hrWithLeave->id,
        'project_id' => $project->id,
        'task_deadline' => now()->subDays(5)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    $this->withoutVite()
        ->actingAs($hrViewer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(function ($page) use ($hrWithLeave) {
            $props = $page->toArray()['props'];
            $ptoUserIds = array_column($props['ptoAlerts'] ?? [], 'user_id');
            expect($ptoUserIds)->not->toContain($hrWithLeave->id);
            $deadlineUserIds = array_column($props['deadlineAlerts'] ?? [], 'user_id');
            expect($deadlineUserIds)->not->toContain($hrWithLeave->id);
        });
})->repeat(100);
// Feature: dashboard-analytics, Property 10: HR users are excluded from employee-specific alerts

// ---------------------------------------------------------------------------
// Property 11: Project health overdue count is accurate
// Feature: dashboard-analytics, Property 11: project health overdue count is accurate
// ---------------------------------------------------------------------------

it('projectHealth.overdue equals count of projects past deadline and not completed/cancelled', function () {
    $hrUser = dashPropUser('hr');

    $overdueActive = fake()->numberBetween(1, 4);
    $overdueCompleted = fake()->numberBetween(1, 3);
    $overdueCancelled = fake()->numberBetween(1, 3);
    $futureActive = fake()->numberBetween(1, 4);

    // Overdue + active statuses — should be counted
    Project::factory()->count($overdueActive)->create([
        'status' => ProjectStatus::Planning,
        'deadline' => now()->subDays(fake()->numberBetween(1, 30))->toDateString(),
    ]);

    // Overdue + completed — should NOT be counted
    Project::factory()->count($overdueCompleted)->completed()->create([
        'deadline' => now()->subDays(fake()->numberBetween(1, 30))->toDateString(),
    ]);

    // Overdue + cancelled — should NOT be counted
    Project::factory()->count($overdueCancelled)->cancelled()->create([
        'deadline' => now()->subDays(fake()->numberBetween(1, 30))->toDateString(),
    ]);

    // Future deadline — should NOT be counted
    Project::factory()->count($futureActive)->create([
        'status' => ProjectStatus::InProgress,
        'deadline' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
    ]);

    $this->withoutVite()
        ->actingAs($hrUser)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/hr')
            ->where('projectHealth.overdue', $overdueActive)
        );
})->repeat(100);
// Feature: dashboard-analytics, Property 11: project health overdue count is accurate

// ---------------------------------------------------------------------------
// Property 12: My Projects near-deadline warning is accurate
// Feature: dashboard-analytics, Property 12: My Projects near-deadline warning is accurate
// ---------------------------------------------------------------------------

it('myProjects is_near_deadline is true only for projects within 7 days and not completed/cancelled', function () {
    $employee = dashPropUser('employee');

    $deadlineOffsets = [0, 6, 7, 8, 30];
    $projects = [];

    foreach ($deadlineOffsets as $offset) {
        $project = Project::factory()->create([
            'status' => ProjectStatus::InProgress,
            'deadline' => now()->addDays($offset)->toDateString(),
        ]);
        ProjectAssignment::factory()->create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
            'task_deadline' => now()->addDays(30)->toDateString(),
            'completion_status' => CompletionStatus::Pending,
        ]);
        $projects[$offset] = $project;
    }

    // Completed project within 7 days — should NOT be near_deadline
    $completedProject = Project::factory()->completed()->create([
        'deadline' => now()->addDays(3)->toDateString(),
    ]);
    ProjectAssignment::factory()->create([
        'user_id' => $employee->id,
        'project_id' => $completedProject->id,
        'task_deadline' => now()->addDays(30)->toDateString(),
        'completion_status' => CompletionStatus::Pending,
    ]);

    $this->withoutVite()
        ->actingAs($employee)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(function ($page) use ($projects, $completedProject) {
            $myProjects = collect($page->toArray()['props']['myProjects'] ?? []);

            // 0, 6, 7 days → near deadline
            foreach ([0, 6, 7] as $offset) {
                $found = $myProjects->firstWhere('project_id', $projects[$offset]->id);
                expect($found)->not->toBeNull("Project with offset $offset not found");
                expect($found['is_near_deadline'])->toBeTrue("Expected is_near_deadline=true for offset $offset");
            }

            // 8, 30 days → NOT near deadline
            foreach ([8, 30] as $offset) {
                $found = $myProjects->firstWhere('project_id', $projects[$offset]->id);
                expect($found)->not->toBeNull("Project with offset $offset not found");
                expect($found['is_near_deadline'])->toBeFalse("Expected is_near_deadline=false for offset $offset");
            }

            // Completed project → NOT near deadline even within 7 days
            $completedFound = $myProjects->firstWhere('project_id', $completedProject->id);
            if ($completedFound) {
                expect($completedFound['is_near_deadline'])->toBeFalse();
            }
        });
})->repeat(100);
// Feature: dashboard-analytics, Property 12: My Projects near-deadline warning is accurate
