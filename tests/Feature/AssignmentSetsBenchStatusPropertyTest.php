<?php

use App\Enums\BenchStatus;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Property 12: Assigning an employee sets bench status to assigned
// ---------------------------------------------------------------------------

it('assigning an employee sets bench status to assigned', function () {
    // Feature: project-management, Property 12: assigning an employee sets bench status to assigned
    $hr = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('hr');

    $employee = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
        'bench_status' => BenchStatus::OnBench,
    ])->assignRole('employee');

    $project = Project::factory()->create();

    $this->actingAs($hr)
        ->post(route('projects.assignments.store', $project), [
            'user_id' => $employee->id,
            'task_description' => fake()->sentence(),
            'task_deadline' => now()->addDays(rand(1, 10))->toDateString(),
        ])
        ->assertRedirect(route('projects.show', $project));

    expect($employee->fresh()->bench_status)->toBe(BenchStatus::Assigned);
})->repeat(100);
