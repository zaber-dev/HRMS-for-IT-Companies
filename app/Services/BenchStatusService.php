<?php

namespace App\Services;

use App\Enums\BenchStatus;
use App\Models\ProjectAssignment;
use App\Models\User;

class BenchStatusService
{
    /**
     * Recalculate and persist the bench status for the given employee.
     *
     * Sets bench_status to BenchStatus::Assigned if the employee has any pending
     * project assignments, or BenchStatus::OnBench if none exist.
     */
    public function recalculate(User $employee): void
    {
        $hasPendingAssignment = ProjectAssignment::where('user_id', $employee->id)
            ->where('completion_status', 'pending')
            ->exists();

        $employee->bench_status = $hasPendingAssignment
            ? BenchStatus::Assigned
            : BenchStatus::OnBench;

        $employee->save();
    }
}
