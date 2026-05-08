<?php

use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create an active HR user for skill delete cascade property tests.
 */
function skillDeleteCascadeUser(): User
{
    return User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('hr');
}

// ---------------------------------------------------------------------------
// Property 8: Deleting a skill removes it from all project required-skills
// ---------------------------------------------------------------------------

it('removes a skill from all project required-skills when the skill is deleted', function () {
    // Feature: project-management, Property 8: deleting a skill removes it from all project required-skills
    $hr = skillDeleteCascadeUser();
    $skill = Skill::factory()->create();

    $projectCount = rand(1, 5);
    $projects = Project::factory()->count($projectCount)->create();

    foreach ($projects as $project) {
        $project->skills()->attach($skill->id);
    }

    expect(DB::table('project_skill')->where('skill_id', $skill->id)->count())->toBe($projectCount);

    $this->actingAs($hr)
        ->delete(route('skills.destroy', $skill))
        ->assertRedirect(route('skills.index'));

    expect(DB::table('project_skill')->where('skill_id', $skill->id)->count())->toBe(0);
})->repeat(100);
