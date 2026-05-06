<?php

namespace Database\Factories;

use App\Models\Skill;
use App\Models\SkillAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkillAssignment>
 */
class SkillAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'skill_id' => Skill::factory(),
            'source' => 'self',
        ];
    }

    /**
     * Indicate that the assignment was made by a privileged user.
     */
    public function privileged(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'privileged',
        ]);
    }
}
