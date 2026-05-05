<?php

namespace Database\Factories;

use App\Enums\ApprovalDecision;
use App\Models\ApprovalAction;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalAction>
 */
class ApprovalActionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'leave_request_id' => LeaveRequest::factory(),
            'user_id' => User::factory(),
            'decision' => ApprovalDecision::Approved,
            'is_bypass' => false,
            'comment' => null,
        ];
    }

    /**
     * Set is_bypass to true.
     */
    public function bypass(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_bypass' => true,
        ]);
    }

    /**
     * Set decision to rejected with a random comment.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'decision' => ApprovalDecision::Rejected,
            'comment' => fake()->sentence(),
        ]);
    }
}
