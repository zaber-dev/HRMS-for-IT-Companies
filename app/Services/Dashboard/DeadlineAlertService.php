<?php

namespace App\Services\Dashboard;

use App\Enums\CompletionStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeadlineAlertService
{
    /**
     * Get all overdue pending project assignments for employees.
     *
     * Returns one entry per overdue assignment (not per employee).
     * Only users with the `employee` role are included.
     * Results are sorted descending by days overdue.
     *
     * @return array<int, array{user_id: int, name: string, project_id: int, project_name: string, task_description: string, task_deadline: string, days_overdue: int}>
     */
    public function get(): array
    {
        $today = now()->toDateString();

        $employeeIds = User::role('employee')->pluck('id');

        $results = DB::table('project_assignments')
            ->join('users', 'project_assignments.user_id', '=', 'users.id')
            ->join('projects', 'project_assignments.project_id', '=', 'projects.id')
            ->whereIn('project_assignments.user_id', $employeeIds)
            ->where('project_assignments.completion_status', CompletionStatus::Pending->value)
            ->whereDate('project_assignments.task_deadline', '<', $today)
            ->selectRaw(
                'users.id as user_id,
                 users.name,
                 projects.id as project_id,
                 projects.name as project_name,
                 project_assignments.task_description,
                 project_assignments.task_deadline,
                 CAST(julianday(?) - julianday(project_assignments.task_deadline) AS INTEGER) as days_overdue',
                [$today]
            )
            ->orderByDesc('days_overdue')
            ->get();

        return $results->map(fn ($row) => [
            'user_id' => (int) $row->user_id,
            'name' => $row->name,
            'project_id' => (int) $row->project_id,
            'project_name' => $row->project_name,
            'task_description' => $row->task_description,
            'task_deadline' => $row->task_deadline,
            'days_overdue' => (int) $row->days_overdue,
        ])->all();
    }
}
