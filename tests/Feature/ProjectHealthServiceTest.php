<?php

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Services\Dashboard\ProjectHealthService;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->service = app(ProjectHealthService::class);
});

// ---------------------------------------------------------------------------
// by_status — all five groups always present
// ---------------------------------------------------------------------------

test('all five status groups are present even with no projects', function () {
    $result = $this->service->get();

    expect($result['by_status'])->toHaveKeys(['planning', 'in_progress', 'on_hold', 'completed', 'cancelled']);
    foreach ($result['by_status'] as $count) {
        expect($count)->toBe(0);
    }
});

test('by_status counts reflect actual project statuses', function () {
    Project::factory()->count(3)->create(['status' => ProjectStatus::Planning]);
    Project::factory()->count(2)->inProgress()->create();
    Project::factory()->count(1)->onHold()->create();
    Project::factory()->count(4)->completed()->create();
    Project::factory()->count(2)->cancelled()->create();

    $result = $this->service->get();

    expect($result['by_status']['planning'])->toBe(3);
    expect($result['by_status']['in_progress'])->toBe(2);
    expect($result['by_status']['on_hold'])->toBe(1);
    expect($result['by_status']['completed'])->toBe(4);
    expect($result['by_status']['cancelled'])->toBe(2);
});

// ---------------------------------------------------------------------------
// overdue count
// ---------------------------------------------------------------------------

test('overdue count is zero when no projects exist', function () {
    $result = $this->service->get();

    expect($result['overdue'])->toBe(0);
});

test('overdue counts projects with past deadline and non-terminal status', function () {
    // Overdue active projects — should be counted
    Project::factory()->count(2)->create([
        'status' => ProjectStatus::Planning,
        'deadline' => now()->subDays(5)->toDateString(),
    ]);
    Project::factory()->count(1)->inProgress()->create([
        'deadline' => now()->subDays(1)->toDateString(),
    ]);

    $result = $this->service->get();

    expect($result['overdue'])->toBe(3);
});

test('completed projects with past deadline are excluded from overdue', function () {
    Project::factory()->count(3)->completed()->create([
        'deadline' => now()->subDays(10)->toDateString(),
    ]);

    $result = $this->service->get();

    expect($result['overdue'])->toBe(0);
});

test('cancelled projects with past deadline are excluded from overdue', function () {
    Project::factory()->count(2)->cancelled()->create([
        'deadline' => now()->subDays(10)->toDateString(),
    ]);

    $result = $this->service->get();

    expect($result['overdue'])->toBe(0);
});

test('projects with future deadline are not overdue', function () {
    Project::factory()->count(3)->create([
        'status' => ProjectStatus::InProgress,
        'deadline' => now()->addDays(10)->toDateString(),
    ]);

    $result = $this->service->get();

    expect($result['overdue'])->toBe(0);
});

test('overdue count is accurate with mixed statuses and deadlines', function () {
    // Should be counted (overdue + active)
    Project::factory()->create(['status' => ProjectStatus::Planning, 'deadline' => now()->subDays(3)->toDateString()]);
    Project::factory()->inProgress()->create(['deadline' => now()->subDays(1)->toDateString()]);
    Project::factory()->onHold()->create(['deadline' => now()->subDays(7)->toDateString()]);

    // Should NOT be counted
    Project::factory()->completed()->create(['deadline' => now()->subDays(5)->toDateString()]);
    Project::factory()->cancelled()->create(['deadline' => now()->subDays(5)->toDateString()]);
    Project::factory()->create(['status' => ProjectStatus::Planning, 'deadline' => now()->addDays(5)->toDateString()]);

    $result = $this->service->get();

    expect($result['overdue'])->toBe(3);
});
