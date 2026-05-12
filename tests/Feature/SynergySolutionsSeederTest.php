<?php

use App\Enums\CompletionStatus;
use App\Enums\LeaveStatus;
use App\Enums\ProjectStatus;
use App\Models\AuditLog;
use App\Models\LeaveRequest;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use App\Services\Dashboard\DeadlineAlertService;
use App\Services\Dashboard\PtoAlertService;
use App\Services\Dashboard\SkillCoverageService;

test('synergy solutions seeder creates demo data', function () {
    $this->seed();

    expect(User::role('super_admin')->count())->toBe(1);
    expect(User::role('admin')->count())->toBe(2);
    expect(User::role('hr')->count())->toBe(4);
    expect(User::role('employee')->count())->toBe(8);

    expect(SkillCategory::count())->toBe(5);
    expect(Skill::count())->toBe(50);

    expect(Project::count())->toBe(10);
    expect(Project::where('status', ProjectStatus::Completed)->count())->toBeGreaterThan(0);
    expect(Project::where('status', ProjectStatus::InProgress)->count())->toBeGreaterThan(0);

    expect(ProjectAssignment::where('completion_status', CompletionStatus::Complete)->count())
        ->toBeGreaterThan(0);
    expect(ProjectAssignment::where('completion_status', CompletionStatus::Pending)->count())
        ->toBeGreaterThan(0);

    expect(LeaveRequest::whereIn('status', [
        LeaveStatus::PendingHr,
        LeaveStatus::PendingAdmin,
        LeaveStatus::PendingSuperAdmin,
    ])->count())->toBeGreaterThan(0);
    expect(LeaveRequest::where('status', LeaveStatus::Approved)->count())->toBeGreaterThan(0);
    expect(LeaveRequest::where('status', LeaveStatus::Rejected)->count())->toBeGreaterThan(0);

    expect(app(PtoAlertService::class)->get())->not->toBeEmpty();
    expect(app(DeadlineAlertService::class)->get())->not->toBeEmpty();
    expect(app(SkillCoverageService::class)->get())->not->toBeEmpty();
    expect(AuditLog::count())->toBeGreaterThan(0);
});
