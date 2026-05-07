<?php

namespace Database\Factories;

use App\Enums\CompletionStatus;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectAssignment>
 */
class ProjectAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'task_description' => fake()->sentence(),
            'task_deadline' => now()->addDays(20)->toDateString(),
            'completion_status' => CompletionStatus::Pending,
        ];
    }

    /**
     * Set completion status to complete.
     */
    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'completion_status' => CompletionStatus::Complete,
        ]);
    }
}
