<?php

use App\Enums\BenchStatus;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\User;
use App\Services\BenchStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ---------------------------------------------------------------------------
// recalculate()
// ---------------------------------------------------------------------------

describe('recalculate()', function () {
    /**
     * Requirements 6.4: When an employee has no pending assignments,
     * bench_status should be set to on_bench.
     */
    it('sets on_bench when employee has no assignments', function () {
        $service = new BenchStatusService;
        $employee = User::factory()->create(['bench_status' => BenchStatus::Assigned]);

        $service->recalculate($employee);

        expect($employee->fresh()->bench_status)->toBe(BenchStatus::OnBench);
    });

    /**
     * Requirements 6.5: When an employee has at least one pending assignment,
     * bench_status should be set to assigned.
     */
    it('sets assigned when employee has at least one pending assignment', function () {
        $service = new BenchStatusService;
        $employee = User::factory()->create(['bench_status' => BenchStatus::OnBench]);

        ProjectAssignment::factory()->create(['user_id' => $employee->id]);

        $service->recalculate($employee);

        expect($employee->fresh()->bench_status)->toBe(BenchStatus::Assigned);
    });

    /**
     * Requirements 6.3, 6.4: When all of an employee's assignments are complete,
     * bench_status should revert to on_bench.
     */
    it('sets on_bench when all assignments are complete', function () {
        $service = new BenchStatusService;
        $employee = User::factory()->create(['bench_status' => BenchStatus::Assigned]);
        $project = Project::factory()->create();

        ProjectAssignment::factory()->complete()->create([
            'user_id' => $employee->id,
            'project_id' => $project->id,
        ]);

        $service->recalculate($employee);

        expect($employee->fresh()->bench_status)->toBe(BenchStatus::OnBench);
    });

    it('stays assigned when employee has a mix of pending and complete assignments', function () {
        $service = new BenchStatusService;
        $employee = User::factory()->create(['bench_status' => BenchStatus::OnBench]);
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        ProjectAssignment::factory()->complete()->create([
            'user_id' => $employee->id,
            'project_id' => $projectA->id,
        ]);

        ProjectAssignment::factory()->create([
            'user_id' => $employee->id,
            'project_id' => $projectB->id,
        ]);

        $service->recalculate($employee);

        expect($employee->fresh()->bench_status)->toBe(BenchStatus::Assigned);
    });

    it('only considers assignments belonging to the given employee', function () {
        $service = new BenchStatusService;
        $employeeA = User::factory()->create(['bench_status' => BenchStatus::Assigned]);
        $employeeB = User::factory()->create();

        // Only employee B has a pending assignment
        ProjectAssignment::factory()->create(['user_id' => $employeeB->id]);

        $service->recalculate($employeeA);

        expect($employeeA->fresh()->bench_status)->toBe(BenchStatus::OnBench);
    });
});
