<?php

namespace App\Services\Dashboard;

use App\Enums\BenchStatus;
use App\Models\User;

class WorkforceSummaryService
{
    /**
     * Get a summary of the workforce, counting only users with the `employee` role.
     *
     * @return array{total_employees: int, on_bench: int, assigned: int, deactivated: int}
     */
    public function get(): array
    {
        $employees = User::role('employee')->get();

        $totalEmployees = $employees->count();
        $onBench = $employees->where('is_active', true)->where('bench_status', BenchStatus::OnBench)->count();
        $assigned = $employees->where('is_active', true)->where('bench_status', BenchStatus::Assigned)->count();
        $deactivated = $employees->where('is_active', false)->count();

        return [
            'total_employees' => $totalEmployees,
            'on_bench' => $onBench,
            'assigned' => $assigned,
            'deactivated' => $deactivated,
        ];
    }
}
