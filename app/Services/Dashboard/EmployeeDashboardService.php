<?php

namespace App\Services\Dashboard;

use App\Enums\CompletionStatus;
use App\Enums\LeaveStatus;
use App\Enums\ProjectStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmployeeDashboardService
{
    /**
     * Aggregate all employee-facing dashboard data for the given employee.
     *
     * @return array{
     *     personal_stats: array{name: string, role: string|null, bench_status: string, skills_count: int, created_at: string},
     *     my_tasks: array<int, array{project_id: int, project_name: string, task_description: string, task_deadline: string, days_remaining: int}>,
     *     my_projects: array<int, array{project_id: int, name: string, status: string, deadline: string|null, is_near_deadline: bool}>,
     *     my_leave: array{approved_days: int, pending_count: int, most_recent: array{id: int, status: string, start_date: string, end_date: string}|null},
     *     my_skills: array<string, array<int, array{id: int, name: string, source: string}>>
     * }
     */
    public function get(User $employee): array
    {
        return [
            'personal_stats' => $this->getPersonalStats($employee),
            'my_tasks' => $this->getMyTasks($employee),
            'my_projects' => $this->getMyProjects($employee),
            'my_leave' => $this->getMyLeave($employee),
            'my_skills' => $this->getMySkills($employee),
        ];
    }

    /**
     * @return array{name: string, role: string|null, bench_status: string, skills_count: int, created_at: string}
     */
    private function getPersonalStats(User $employee): array
    {
        $role = $employee->getRoleNames()->first();
        $skillsCount = $employee->skillAssignments()->count();

        return [
            'name' => $employee->name,
            'role' => $role,
            'bench_status' => $employee->bench_status?->value ?? 'on_bench',
            'skills_count' => $skillsCount,
            'created_at' => $employee->created_at->toDateString(),
        ];
    }

    /**
     * Pending assignments with days_remaining (positive = future, negative = overdue).
     * Sorted: overdue first (most negative), then ascending by nearest deadline.
     *
     * @return array<int, array{assignment_id: int, project_id: int, project_name: string, task_description: string, task_deadline: string, days_remaining: int}>
     */
    private function getMyTasks(User $employee): array
    {
        $today = now()->toDateString();

        $results = DB::table('project_assignments')
            ->join('projects', 'project_assignments.project_id', '=', 'projects.id')
            ->where('project_assignments.user_id', $employee->id)
            ->where('project_assignments.completion_status', CompletionStatus::Pending->value)
            ->selectRaw(
                'project_assignments.id as assignment_id,
                 projects.id as project_id,
                 projects.name as project_name,
                 project_assignments.task_description,
                 DATE(project_assignments.task_deadline) as task_deadline,
                 CAST(julianday(DATE(project_assignments.task_deadline)) - julianday(?) AS INTEGER) as days_remaining',
                [$today]
            )
            ->orderByRaw('days_remaining ASC')
            ->get();

        return $results->map(fn ($row) => [
            'assignment_id' => (int) $row->assignment_id,
            'project_id' => (int) $row->project_id,
            'project_name' => $row->project_name,
            'task_description' => $row->task_description,
            'task_deadline' => $row->task_deadline,
            'days_remaining' => (int) $row->days_remaining,
        ])->all();
    }

    /**
     * Projects the employee is assigned to, with is_near_deadline flag.
     * is_near_deadline = deadline within 7 days AND status not completed/cancelled.
     *
     * @return array<int, array{project_id: int, name: string, status: string, deadline: string|null, is_near_deadline: bool}>
     */
    private function getMyProjects(User $employee): array
    {
        $today = now()->toDateString();
        $nearDeadlineDate = now()->addDays(7)->toDateString();

        $excludedStatuses = [
            ProjectStatus::Completed->value,
            ProjectStatus::Cancelled->value,
        ];

        $projects = DB::table('projects')
            ->join('project_assignments', 'projects.id', '=', 'project_assignments.project_id')
            ->where('project_assignments.user_id', $employee->id)
            ->selectRaw('projects.id as project_id, projects.name, projects.status, DATE(projects.deadline) as deadline')
            ->distinct()
            ->get();

        return $projects->map(function ($row) use ($today, $nearDeadlineDate, $excludedStatuses) {
            $isNearDeadline = false;

            if ($row->deadline !== null && ! in_array($row->status, $excludedStatuses)) {
                $isNearDeadline = $row->deadline >= $today && $row->deadline <= $nearDeadlineDate;
            }

            return [
                'project_id' => (int) $row->project_id,
                'name' => $row->name,
                'status' => $row->status,
                'deadline' => $row->deadline,
                'is_near_deadline' => $isNearDeadline,
            ];
        })->all();
    }

    /**
     * Leave summary: approved days in current year, pending count, most recent request.
     *
     * @return array{approved_days: int, pending_count: int, most_recent: array{id: int, status: string, start_date: string, end_date: string}|null}
     */
    private function getMyLeave(User $employee): array
    {
        $year = now()->year;

        $pendingStatuses = [
            LeaveStatus::PendingHr->value,
            LeaveStatus::PendingAdmin->value,
            LeaveStatus::PendingSuperAdmin->value,
        ];

        $approvedDays = DB::table('leave_requests')
            ->where('user_id', $employee->id)
            ->where('status', LeaveStatus::Approved->value)
            ->whereYear('start_date', $year)
            ->selectRaw('SUM(julianday(end_date) - julianday(start_date) + 1) as total')
            ->value('total');

        $pendingCount = DB::table('leave_requests')
            ->where('user_id', $employee->id)
            ->whereIn('status', $pendingStatuses)
            ->count();

        $mostRecentRow = DB::table('leave_requests')
            ->where('user_id', $employee->id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->selectRaw('id, status, DATE(start_date) as start_date, DATE(end_date) as end_date')
            ->first();

        $mostRecent = $mostRecentRow ? [
            'id' => (int) $mostRecentRow->id,
            'status' => $mostRecentRow->status,
            'start_date' => $mostRecentRow->start_date,
            'end_date' => $mostRecentRow->end_date,
        ] : null;

        return [
            'approved_days' => (int) ($approvedDays ?? 0),
            'pending_count' => $pendingCount,
            'most_recent' => $mostRecent,
        ];
    }

    /**
     * Skills grouped by category name, each with id, name, source.
     *
     * @return array<string, array<int, array{id: int, name: string, source: string}>>
     */
    private function getMySkills(User $employee): array
    {
        $rows = DB::table('skill_assignments')
            ->join('skills', 'skill_assignments.skill_id', '=', 'skills.id')
            ->join('skill_categories', 'skills.skill_category_id', '=', 'skill_categories.id')
            ->where('skill_assignments.user_id', $employee->id)
            ->select(
                'skills.id',
                'skills.name',
                'skill_assignments.source',
                'skill_categories.name as category_name'
            )
            ->orderBy('skill_categories.name')
            ->orderBy('skills.name')
            ->get();

        $grouped = [];

        foreach ($rows as $row) {
            $grouped[$row->category_name][] = [
                'id' => (int) $row->id,
                'name' => $row->name,
                'source' => $row->source,
            ];
        }

        return $grouped;
    }
}
