<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'features_list' => fake()->optional()->paragraph(),
            'status' => ProjectStatus::Planning,
            'deadline' => now()->addDays(30)->toDateString(),
        ];
    }

    /**
     * Set status to in_progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::InProgress,
        ]);
    }

    /**
     * Set status to on_hold.
     */
    public function onHold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::OnHold,
        ]);
    }

    /**
     * Set status to completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::Completed,
        ]);
    }

    /**
     * Set status to cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::Cancelled,
        ]);
    }

    /**
     * Set a specific deadline date.
     */
    public function withDeadline(Carbon $date): static
    {
        return $this->state(fn (array $attributes) => [
            'deadline' => $date->toDateString(),
        ]);
    }
}
