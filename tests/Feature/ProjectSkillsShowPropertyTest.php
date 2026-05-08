<?php

use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create an active HR user for project skills show property tests.
 */
function projectSkillsShowPropUser(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('hr');
}

// ---------------------------------------------------------------------------
// Property 7: Required skills are fully reflected on the show page
// ---------------------------------------------------------------------------

it('reflects all required skills on the project show page', function () {
    // Feature: project-management, Property 7: required skills are fully reflected on the show page
    $hr = projectSkillsShowPropUser();

    // Create a random set of 0–5 skills and attach them to the project via the pivot
    $skillCount = fake()->numberBetween(0, 5);
    $skills = Skill::factory()->count($skillCount)->create(['is_active' => true]);

    $project = Project::factory()->create();
    $project->skills()->sync($skills->pluck('id')->all());

    $attachedSkillIds = $skills->pluck('id')->sort()->values()->all();

    $this->withoutVite()
        ->actingAs($hr)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/show')
            ->where('project.skills', function ($responseSkills) use ($attachedSkillIds) {
                $responseSkillIds = collect($responseSkills)->pluck('id')->sort()->values()->all();

                return $responseSkillIds === $attachedSkillIds;
            })
        );
})->repeat(100);
