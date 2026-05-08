<?php

use App\Enums\BenchStatus;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create an active privileged user (hr) for making suggestion requests.
 */
function suggestionsHrUser(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('hr');
}

/**
 * Create an active employee with the given bench status and optional skills.
 *
 * @param  array<int>  $skillIds
 */
function suggestionsEmployee(BenchStatus $benchStatus, array $skillIds = []): User
{
    $employee = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
        'bench_status' => $benchStatus,
    ])->assignRole('employee');

    foreach ($skillIds as $skillId) {
        $employee->skillAssignments()->create(['skill_id' => $skillId, 'source' => 'privileged']);
    }

    return $employee;
}

// ---------------------------------------------------------------------------
// Property 9: Assignment suggestions are exactly employees with matching skills
//             who are on bench
// ---------------------------------------------------------------------------

it('suggestions contain exactly employees with matching skills who are on bench', function () {
    // Feature: project-management, Property 9: assignment suggestions are exactly employees with matching skills who are on bench

    $hr = suggestionsHrUser();

    // Create a pool of skills; pick a random subset as project required skills
    $allSkills = Skill::factory()->count(fake()->numberBetween(3, 6))->create(['is_active' => true]);
    $requiredSkillCount = fake()->numberBetween(1, $allSkills->count());
    $requiredSkills = $allSkills->random($requiredSkillCount);
    $nonRequiredSkills = $allSkills->diff($requiredSkills);

    $project = Project::factory()->create();
    $project->skills()->attach($requiredSkills->pluck('id'));

    $requiredSkillIds = $requiredSkills->pluck('id')->toArray();

    // Group A: on bench WITH at least one matching skill → MUST appear in suggestions
    $matchingOnBenchCount = fake()->numberBetween(1, 3);
    $matchingOnBench = collect();
    for ($i = 0; $i < $matchingOnBenchCount; $i++) {
        // Give each employee a random subset of required skills (at least 1)
        $employeeSkillIds = $requiredSkills->random(fake()->numberBetween(1, $requiredSkillCount))->pluck('id')->toArray();
        $matchingOnBench->push(suggestionsEmployee(BenchStatus::OnBench, $employeeSkillIds));
    }

    // Group B: assigned (not on bench) WITH matching skills → must NOT appear
    $matchingAssignedCount = fake()->numberBetween(1, 3);
    for ($i = 0; $i < $matchingAssignedCount; $i++) {
        $employeeSkillIds = $requiredSkills->random(fake()->numberBetween(1, $requiredSkillCount))->pluck('id')->toArray();
        suggestionsEmployee(BenchStatus::Assigned, $employeeSkillIds);
    }

    // Group C: on bench WITHOUT any matching skill → must NOT appear
    $noMatchOnBenchCount = fake()->numberBetween(1, 3);
    for ($i = 0; $i < $noMatchOnBenchCount; $i++) {
        // Only assign non-required skills (or no skills at all)
        $employeeSkillIds = $nonRequiredSkills->isNotEmpty()
            ? $nonRequiredSkills->random(fake()->numberBetween(0, $nonRequiredSkills->count()))->pluck('id')->toArray()
            : [];
        suggestionsEmployee(BenchStatus::OnBench, $employeeSkillIds);
    }

    // Group D: assigned WITHOUT matching skills → must NOT appear
    $noMatchAssignedCount = fake()->numberBetween(0, 2);
    for ($i = 0; $i < $noMatchAssignedCount; $i++) {
        $employeeSkillIds = $nonRequiredSkills->isNotEmpty()
            ? $nonRequiredSkills->random(fake()->numberBetween(0, $nonRequiredSkills->count()))->pluck('id')->toArray()
            : [];
        suggestionsEmployee(BenchStatus::Assigned, $employeeSkillIds);
    }

    $response = $this->withoutVite()
        ->actingAs($hr)
        ->get(route('projects.assignments.create', $project));

    $response->assertOk();

    $response->assertInertia(function ($page) use ($matchingOnBench, $requiredSkillIds) {
        $page->has('suggestions');

        $suggestions = collect($page->toArray()['props']['suggestions']);
        $suggestionIds = $suggestions->pluck('id')->toArray();

        // Every employee in Group A must appear in suggestions
        foreach ($matchingOnBench as $employee) {
            expect($suggestionIds)->toContain($employee->id);
        }

        // Every suggestion must satisfy both conditions:
        // (a) bench_status = on_bench  AND  (b) at least one matching skill
        foreach ($suggestions as $suggestion) {
            expect($suggestion['bench_status'])->toBe(BenchStatus::OnBench->value);

            $employeeSkillIds = collect($suggestion['skills'])->pluck('id')->toArray();
            $hasMatchingSkill = count(array_intersect($employeeSkillIds, $requiredSkillIds)) > 0;
            expect($hasMatchingSkill)->toBeTrue();
        }

        // Suggestion count must equal exactly the number of Group A employees
        expect(count($suggestionIds))->toBe($matchingOnBench->count());
    });
})->repeat(100);
