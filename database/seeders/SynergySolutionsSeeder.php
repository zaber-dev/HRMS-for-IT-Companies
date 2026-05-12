<?php

namespace Database\Seeders;

use App\Enums\ApprovalDecision;
use App\Enums\AssignmentSource;
use App\Enums\BenchStatus;
use App\Enums\CompletionStatus;
use App\Enums\LeaveStatus;
use App\Enums\ProjectStatus;
use App\Models\ApprovalAction;
use App\Models\LeaveRequest;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Skill;
use App\Models\SkillAssignment;
use App\Models\SkillCategory;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SynergySolutionsSeeder extends Seeder
{
    private const EMAIL_DOMAIN = 'synergybpo.biz';

    private ?Skill $coverageGapSkill = null;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = $this->seedAdmins();
        $hrs = $this->seedHr();
        $employees = $this->seedEmployees();

        $skills = $this->seedSkills();
        $projects = $this->seedProjects();

        $this->seedSkillAssignments($employees, $skills);
        $this->seedProjectAssignments($projects, $employees);
        $this->seedLeaveRequests($employees, $admins, $hrs);
        $this->seedAuditLogs($admins, $hrs, $employees, $projects, $skills);
    }

    /**
     * @return array<int, User>
     */
    private function seedAdmins(): array
    {
        $users = [
            ['name' => 'Ava Brooks', 'email' => $this->emailFor('ava.brooks')],
            ['name' => 'Noah Patel', 'email' => $this->emailFor('noah.patel')],
        ];

        return $this->seedUsers($users, 'admin');
    }

    /**
     * @return array<int, User>
     */
    private function seedHr(): array
    {
        $users = [
            ['name' => 'Maya Chen', 'email' => $this->emailFor('maya.chen')],
            ['name' => 'Ethan Ruiz', 'email' => $this->emailFor('ethan.ruiz')],
            ['name' => 'Lena Park', 'email' => $this->emailFor('lena.park')],
            ['name' => 'Owen Scott', 'email' => $this->emailFor('owen.scott')],
        ];

        return $this->seedUsers($users, 'hr');
    }

    /**
     * @return array<int, User>
     */
    private function seedEmployees(): array
    {
        $users = [
            ['name' => 'Jordan Lee', 'email' => $this->emailFor('jordan.lee')],
            ['name' => 'Priya Nair', 'email' => $this->emailFor('priya.nair')],
            ['name' => 'Caleb King', 'email' => $this->emailFor('caleb.king')],
            ['name' => 'Sofia Martinez', 'email' => $this->emailFor('sofia.martinez')],
            ['name' => 'Miles Carter', 'email' => $this->emailFor('miles.carter')],
            ['name' => 'Riley Quinn', 'email' => $this->emailFor('riley.quinn')],
            ['name' => 'Aiden Brooks', 'email' => $this->emailFor('aiden.brooks')],
            ['name' => 'Zoe Rivers', 'email' => $this->emailFor('zoe.rivers')],
        ];

        return $this->seedUsers($users, 'employee', BenchStatus::OnBench);
    }

    /**
     * @param  array<int, array{name: string, email: string}>  $users
     * @return array<int, User>
     */
    private function seedUsers(array $users, string $role, BenchStatus $benchStatus = BenchStatus::OnBench): array
    {
        return collect($users)
            ->map(function (array $data) use ($role, $benchStatus) {
                $user = User::query()->updateOrCreate(
                    ['email' => $data['email']],
                    [
                        'name' => $data['name'],
                        'password' => Hash::make('password'),
                        'is_active' => true,
                        'must_change_password' => false,
                        'bench_status' => $benchStatus,
                    ]
                );

                $user->syncRoles([$role]);

                return $user;
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, Skill>
     */
    private function seedSkills(): array
    {
        $catalog = [
            'Backend Development' => [
                'Laravel API Design',
                'PHP 8.3 Optimization',
                'MySQL Query Tuning',
                'Redis Caching Strategies',
                'Queue Orchestration',
                'RESTful Architecture',
                'Microservice Gateways',
                'Testing with Pest',
                'Schema Design',
                'OAuth Integrations',
            ],
            'Frontend Engineering' => [
                'React 19 Patterns',
                'Inertia SPA Flows',
                'TypeScript Safety',
                'Tailwind UI Systems',
                'Performance Budgets',
                'Component Libraries',
                'State Management',
                'Accessibility Reviews',
                'Vite Build Pipelines',
                'UX Prototyping',
            ],
            'DevOps & Cloud' => [
                'CI Pipeline Design',
                'Docker Deployments',
                'Observability Dashboards',
                'AWS Cost Controls',
                'Kubernetes Operations',
                'Infrastructure as Code',
                'Load Testing',
                'Secrets Management',
                'Blue Green Releases',
                'Backup Automation',
            ],
            'Quality Assurance' => [
                'Automated Test Suites',
                'Regression Planning',
                'Exploratory Testing',
                'Performance Profiling',
                'Security Test Plans',
                'Cross Browser Checks',
                'API Contract Tests',
                'User Acceptance Tests',
                'Defect Triage',
                'Release Validation',
            ],
            'Product Delivery' => [
                'Stakeholder Discovery',
                'Roadmap Facilitation',
                'Sprint Planning',
                'Analytics Instrumentation',
                'Solution Workshops',
                'Risk Mitigation',
                'Release Communications',
                'Client Enablement',
                'Scope Management',
                'Post Launch Reviews',
            ],
        ];

        $skills = [];
        foreach ($catalog as $categoryName => $skillNames) {
            $category = SkillCategory::query()->firstOrCreate(['name' => $categoryName]);

            foreach ($skillNames as $index => $skillName) {
                $skill = Skill::query()->firstOrCreate(
                    ['name' => $skillName],
                    [
                        'skill_category_id' => $category->id,
                        'description' => 'Expertise in '.$skillName.'.',
                        'is_active' => ! in_array($index, [2, 8], true),
                    ]
                );

                if ($skill->skill_category_id !== $category->id) {
                    $skill->update(['skill_category_id' => $category->id]);
                }

                $skills[] = $skill;
            }
        }

        $this->coverageGapSkill = collect($skills)
            ->first(fn (Skill $skill) => $skill->name === 'OAuth Integrations');

        return $skills;
    }

    /**
     * @return array<int, Project>
     */
    private function seedProjects(): array
    {
        $projects = [
            ['name' => 'Unified Client Portal', 'status' => ProjectStatus::InProgress],
            ['name' => 'Legacy System Modernization', 'status' => ProjectStatus::Completed],
            ['name' => 'Field Service Scheduler', 'status' => ProjectStatus::Planning],
            ['name' => 'Analytics Lakehouse', 'status' => ProjectStatus::InProgress],
            ['name' => 'Mobile Sales Enablement', 'status' => ProjectStatus::OnHold],
            ['name' => 'Customer Support Automation', 'status' => ProjectStatus::Completed],
            ['name' => 'Partner Onboarding Hub', 'status' => ProjectStatus::InProgress],
            ['name' => 'Security Hardening Initiative', 'status' => ProjectStatus::Completed],
            ['name' => 'Billing Workflow Revamp', 'status' => ProjectStatus::Planning],
            ['name' => 'Talent Allocation Dashboard', 'status' => ProjectStatus::InProgress],
        ];

        return collect($projects)
            ->map(function (array $project, int $index) {
                $deadline = Carbon::now()->addDays(30 + ($index * 7));

                return Project::query()->updateOrCreate(
                    ['name' => $project['name']],
                    [
                        'description' => 'Delivery engagement for '.$project['name'].'.',
                        'features_list' => 'Discovery, design, build, and enablement.',
                        'status' => $project['status'],
                        'deadline' => $deadline->toDateString(),
                    ]
                );
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, User>  $employees
     * @param  array<int, Skill>  $skills
     */
    private function seedSkillAssignments(array $employees, array $skills): void
    {
        $skillPool = collect($skills)
            ->when($this->coverageGapSkill, fn ($collection) => $collection->reject(
                fn (Skill $skill) => $skill->id === $this->coverageGapSkill?->id
            ));

        if ($this->coverageGapSkill) {
            SkillAssignment::query()
                ->where('skill_id', $this->coverageGapSkill->id)
                ->delete();
        }

        foreach ($employees as $employee) {
            $employeeSkills = $skillPool->random(4)->values();

            foreach ($employeeSkills as $skill) {
                SkillAssignment::query()->updateOrCreate(
                    ['user_id' => $employee->id, 'skill_id' => $skill->id],
                    [
                        'source' => fake()->boolean(30)
                            ? AssignmentSource::Privileged
                            : AssignmentSource::Self,
                    ]
                );
            }
        }
    }

    /**
     * @param  array<int, Project>  $projects
     * @param  array<int, User>  $employees
     */
    private function seedProjectAssignments(array $projects, array $employees): void
    {
        $employeePool = collect($employees);
        $gapSkillId = $this->coverageGapSkill?->id;
        $overdueProjectId = null;

        foreach ($projects as $project) {
            $assignees = $employeePool->random(3)->values();
            $projectSkills = Skill::query()->inRandomOrder()->limit(4)->get();

            $project->skills()->syncWithoutDetaching($projectSkills->pluck('id')->all());
            if ($gapSkillId && in_array($project->status, [ProjectStatus::Planning, ProjectStatus::InProgress], true)) {
                $project->skills()->syncWithoutDetaching([$gapSkillId]);
                $gapSkillId = null;
                $overdueProjectId = $project->id;
            }

            foreach ($assignees as $assignee) {
                $completionStatus = $project->status === ProjectStatus::Completed
                    ? CompletionStatus::Complete
                    : (fake()->boolean(30) ? CompletionStatus::Complete : CompletionStatus::Pending);

                ProjectAssignment::query()->updateOrCreate(
                    ['project_id' => $project->id, 'user_id' => $assignee->id],
                    [
                        'task_description' => Str::of($project->name)
                            ->append(' delivery task')
                            ->toString(),
                        'task_deadline' => Carbon::parse($project->deadline)->subDays(5)->toDateString(),
                        'completion_status' => $completionStatus,
                    ]
                );
            }
        }

        $assignedEmployeeIds = ProjectAssignment::query()->distinct()->pluck('user_id');
        $employeeIds = collect($employees)->pluck('id');

        User::query()->whereIn('id', $assignedEmployeeIds)->update([
            'bench_status' => BenchStatus::Assigned,
        ]);
        User::query()->whereIn('id', $employeeIds)->whereNotIn('id', $assignedEmployeeIds)->update([
            'bench_status' => BenchStatus::OnBench,
        ]);

        $overdueAssignment = ProjectAssignment::query()
            ->when($overdueProjectId, fn ($query) => $query->where('project_id', $overdueProjectId))
            ->where('completion_status', CompletionStatus::Pending)
            ->first();

        if ($overdueAssignment) {
            $overdueAssignment->update([
                'task_deadline' => now()->subDays(4)->toDateString(),
            ]);
        } else {
            $projectId = $overdueProjectId ?? $projects[0]->id;
            $assigneeId = $employeePool->first()->id;

            ProjectAssignment::query()->updateOrCreate(
                ['project_id' => $projectId, 'user_id' => $assigneeId],
                [
                    'task_description' => 'Overdue delivery follow-up',
                    'task_deadline' => now()->subDays(4)->toDateString(),
                    'completion_status' => CompletionStatus::Pending,
                ]
            );
        }
    }

    /**
     * @param  array<int, User>  $employees
     * @param  array<int, User>  $admins
     * @param  array<int, User>  $hrs
     */
    private function seedLeaveRequests(array $employees, array $admins, array $hrs): void
    {
        $approvers = collect($admins)->merge($hrs);

        LeaveRequest::factory()->pendingHr()->create(['user_id' => $employees[0]->id]);
        LeaveRequest::factory()->pendingAdmin()->create(['user_id' => $employees[1]->id]);
        LeaveRequest::factory()->pendingSuperAdmin()->create(['user_id' => $employees[2]->id]);
        LeaveRequest::factory()->pendingHr()->create(['user_id' => $employees[3]->id]);

        $approvedRequests = [
            LeaveRequest::factory()->approved()->create(['user_id' => $employees[4]->id]),
            LeaveRequest::factory()->approved()->create(['user_id' => $employees[5]->id]),
        ];

        $rejectedRequests = [
            LeaveRequest::factory()->rejected()->create(['user_id' => $employees[6]->id]),
            LeaveRequest::factory()->rejected()->create(['user_id' => $employees[7]->id]),
        ];

        $ptoStart = now()->startOfYear()->addDays(10);
        LeaveRequest::factory()->approved()->create([
            'user_id' => $employees[0]->id,
            'start_date' => $ptoStart->toDateString(),
            'end_date' => $ptoStart->copy()->addDays(14)->toDateString(),
        ]);

        foreach ($approvedRequests as $request) {
            $approver = $approvers->random();
            ApprovalAction::query()->create([
                'leave_request_id' => $request->id,
                'user_id' => $approver->id,
                'decision' => ApprovalDecision::Approved,
                'comment' => 'Approved for planned coverage.',
                'is_bypass' => false,
            ]);
        }

        foreach ($rejectedRequests as $request) {
            $approver = $approvers->random();
            ApprovalAction::query()->create([
                'leave_request_id' => $request->id,
                'user_id' => $approver->id,
                'decision' => ApprovalDecision::Rejected,
                'comment' => 'Reschedule due to active milestones.',
                'is_bypass' => false,
            ]);
        }
    }

    /**
     * @param  array<int, User>  $admins
     * @param  array<int, User>  $hrs
     * @param  array<int, User>  $employees
     * @param  array<int, Project>  $projects
     * @param  array<int, Skill>  $skills
     */
    private function seedAuditLogs(
        array $admins,
        array $hrs,
        array $employees,
        array $projects,
        array $skills
    ): void {
        $logger = app(AuditLogger::class);
        $actors = collect($admins)->merge($hrs)->values();

        if ($actors->isEmpty()) {
            return;
        }

        $logger->log(
            $actors->random(),
            'user.activated',
            $employees[0],
            ['is_active' => false, 'bench_status' => BenchStatus::OnBench->value],
            ['is_active' => true, 'bench_status' => $employees[0]->bench_status->value]
        );

        foreach (collect($projects)->take(3) as $project) {
            $logger->log(
                $actors->random(),
                'project.status_updated',
                $project,
                ['status' => ProjectStatus::Planning->value],
                ['status' => $project->status->value]
            );
        }

        foreach (collect($skills)->take(3) as $skill) {
            $logger->log(
                $actors->random(),
                'skill.created',
                $skill,
                null,
                ['name' => $skill->name, 'is_active' => $skill->is_active]
            );
        }

        $leaveRequests = LeaveRequest::query()->limit(2)->get();
        foreach ($leaveRequests as $leaveRequest) {
            $logger->log(
                $actors->random(),
                'leave.reviewed',
                $leaveRequest,
                ['status' => LeaveStatus::PendingHr->value],
                ['status' => $leaveRequest->status->value]
            );
        }
    }

    private function emailFor(string $localPart): string
    {
        return $localPart.'@'.self::EMAIL_DOMAIN;
    }
}
