<?php

namespace App\Services\Dashboard;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectHealthService
{
    /**
     * Get a summary of project health, grouped by status and overdue count.
     *
     * `by_status` includes all five statuses even when the count is zero.
     * `overdue` counts projects with `deadline < today` and status not `completed` or `cancelled`.
     *
     * @return array{by_status: array<string, int>, overdue: int}
     */
    public function get(): array
    {
        $byStatus = array_fill_keys(
            array_column(ProjectStatus::cases(), 'value'),
            0
        );

        $counts = DB::table('projects')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        foreach ($counts as $status => $count) {
            if (array_key_exists($status, $byStatus)) {
                $byStatus[$status] = (int) $count;
            }
        }

        $excludedStatuses = [
            ProjectStatus::Completed->value,
            ProjectStatus::Cancelled->value,
        ];

        $overdue = Project::query()
            ->whereDate('deadline', '<', now()->toDateString())
            ->whereNotIn('status', $excludedStatuses)
            ->count();

        return [
            'by_status' => $byStatus,
            'overdue' => $overdue,
        ];
    }
}
