<?php

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Skill;
use App\Models\SkillAssignment;
use App\Models\User;
use App\Services\Dashboard\SkillCoverageService;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->service = app(SkillCoverageService::class);
});

function makeCoverageEmployee(bool $active = true): User
{
    return User::factory()->create([
        'is_active' => $active,
        'must_change_password' => false,
        'bench_status' => 'on_bench',
    ])->assignRole('employee');
}

// ---------------------------------------------------------------------------
// Status filtering
// ---------------------------------------------------------------------------

test('planning projects with uncovered skills are included', function () {
    $skill = Skill::factory()->create();
    $project = Project::factory()->create(['status' => ProjectStatus::Planning]);
    $project->skills()->attach($skill->id);

    $results = $this->service->get();

    $ids = array_column($results, 'project_id');
    expect($ids)->toContain($project->id);
});

test('in_progress projects with uncovered skills are included', function () {
    $skill = Skill::factory()->create();
    $project = Project::factory()->inProgress()->create();
    $project->skills()->attach($skill->id);

    $results = $this->service->get();

    $ids = array_column($results, 'project_id');
    expect($ids)->toContain($project->id);
});

test('on_hold projects are excluded', function () {
    $skill = Skill::factory()->create();
    $project = Project::factory()->onHold()->create();
    $project->skills()->attach($skill->id);

    $results = $this->service->get();

    $ids = array_column($results, 'project_id');
    expect($ids)->not->toContain($project->id);
});

test('completed projects are excluded', function () {
    $skill = Skill::factory()->create();
    $project = Project::factory()->completed()->create();
    $project->skills()->attach($skill->id);

    $results = $this->service->get();

    $ids = array_column($results, 'project_id');
    expect($ids)->not->toContain($project->id);
});

test('cancelled projects are excluded', function () {
    $skill = Skill::factory()->create();
    $project = Project::factory()->cancelled()->create();
    $project->skills()->attach($skill->id);

    $results = $this->service->get();

    $ids = array_column($results, 'project_id');
    expect($ids)->not->toContain($project->id);
});

// ---------------------------------------------------------------------------
// Skill coverage
// ---------------------------------------------------------------------------

test('project with full skill coverage is excluded', function () {
    $employee = makeCoverageEmployee();
    $skill = Skill::factory()->create();
    $project = Project::factory()->create(['status' => ProjectStatus::Planning]);
    $project->skills()->attach($skill->id);

    SkillAssignment::factory()->create([
        'user_id' => $employee->id,
        'skill_id' => $skill->id,
    ]);

    $results = $this->service->get();

    $ids = array_column($results, 'project_id');
    expect($ids)->not->toContain($project->id);
});

test('project with no required skills is excluded', function () {
    $project = Project::factory()->create(['status' => ProjectStatus::Planning]);
    // No skills attached

    $results = $this->service->get();

    $ids = array_column($results, 'project_id');
    expect($ids)->not->toContain($project->id);
});

test('uncovered_skills lists only the skills with no active employee coverage', function () {
    $coveredSkill = Skill::factory()->create();
    $uncoveredSkill = Skill::factory()->create();

    $employee = makeCoverageEmployee();
    SkillAssignment::factory()->create([
        'user_id' => $employee->id,
        'skill_id' => $coveredSkill->id,
    ]);

    $project = Project::factory()->create(['status' => ProjectStatus::Planning]);
    $project->skills()->attach([$coveredSkill->id, $uncoveredSkill->id]);

    $results = $this->service->get();

    $entry = collect($results)->firstWhere('project_id', $project->id);
    expect($entry)->not->toBeNull();
    expect($entry['uncovered_skills'])->toBe([$uncoveredSkill->name]);
});

// ---------------------------------------------------------------------------
// Deactivated employees
// ---------------------------------------------------------------------------

test('deactivated employees are not counted as covering a skill', function () {
    $inactiveEmployee = makeCoverageEmployee(active: false);
    $skill = Skill::factory()->create();

    SkillAssignment::factory()->create([
        'user_id' => $inactiveEmployee->id,
        'skill_id' => $skill->id,
    ]);

    $project = Project::factory()->create(['status' => ProjectStatus::Planning]);
    $project->skills()->attach($skill->id);

    $results = $this->service->get();

    $ids = array_column($results, 'project_id');
    expect($ids)->toContain($project->id);
});

test('active employee covering a skill removes it from uncovered list', function () {
    $activeEmployee = makeCoverageEmployee();
    $skill = Skill::factory()->create();

    SkillAssignment::factory()->create([
        'user_id' => $activeEmployee->id,
        'skill_id' => $skill->id,
    ]);

    $project = Project::factory()->create(['status' => ProjectStatus::Planning]);
    $project->skills()->attach($skill->id);

    $results = $this->service->get();

    $ids = array_column($results, 'project_id');
    expect($ids)->not->toContain($project->id);
});
