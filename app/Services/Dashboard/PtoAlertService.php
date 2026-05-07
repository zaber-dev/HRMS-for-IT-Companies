<?php

namespace App\Services\Dashboard;

use App\Enums\LeaveStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PtoAlertService
{
    /**
     * Get employees whose total approved/pending leave days in the current calendar year
     * meet or exceed the given threshold.
     *
     * Calendar days are counted inclusive of start and end date.
     * Only users with the `employee` role are included.
     * Results are sorted descending by total leave days.
     *
     * @return array<int, array{user_id: int, name: string, total_leave_days: int, pending_requests_count: int}>
     */
    public function get(int $threshold = 14): array
    {
        $year = now()->year;

        $countableStatuses = [
            LeaveStatus::Approved->value,
            LeaveStatus::PendingHr->value,
            LeaveStatus::PendingAdmin->value,
            LeaveStatus::PendingSuperAdmin->value,
        ];

        $pendingStatuses = [
            LeaveStatus::PendingHr->value,
            LeaveStatus::PendingAdmin->value,
            LeaveStatus::PendingSuperAdmin->value,
        ];

        $employeeIds = User::role('employee')->pluck('id');

        $pendingPlaceholders = implode(',', array_fill(0, count($pendingStatuses), '?'));

        $results = DB::table('leave_requests')
            ->join('users', 'leave_requests.user_id', '=', 'users.id')
            ->whereIn('leave_requests.user_id', $employeeIds)
            ->whereIn('leave_requests.status', $countableStatuses)
            ->whereYear('leave_requests.start_date', $year)
            ->selectRaw(
                'users.id as user_id,
                 users.name,
                 SUM(julianday(leave_requests.end_date) - julianday(leave_requests.start_date) + 1) as total_leave_days,
                 SUM(CASE WHEN leave_requests.status IN ('.$pendingPlaceholders.') THEN 1 ELSE 0 END) as pending_requests_count',
                $pendingStatuses
            )
            ->groupBy('users.id', 'users.name')
            ->havingRaw('total_leave_days >= ?', [$threshold])
            ->orderByDesc('total_leave_days')
            ->get();

        return $results->map(fn ($row) => [
            'user_id' => (int) $row->user_id,
            'name' => $row->name,
            'total_leave_days' => (int) $row->total_leave_days,
            'pending_requests_count' => (int) $row->pending_requests_count,
        ])->all();
    }
}
