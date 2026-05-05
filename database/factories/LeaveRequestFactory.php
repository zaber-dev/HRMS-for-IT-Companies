<?php

namespace Database\Factories;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = now()->addDay();
        $endDate = $startDate->copy()->addDays(fake()->numberBetween(1, 5));

        return [
            'user_id' => User::factory(),
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'reason' => fake()->sentence(),
            'status' => LeaveStatus::PendingHr,
            'submitted_at' => now(),
        ];
    }

    /**
     * Set status to pending_hr.
     */
    public function pendingHr(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveStatus::PendingHr,
        ]);
    }

    /**
     * Set status to pending_admin.
     */
    public function pendingAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveStatus::PendingAdmin,
        ]);
    }

    /**
     * Set status to pending_super_admin.
     */
    public function pendingSuperAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveStatus::PendingSuperAdmin,
        ]);
    }

    /**
     * Set status to approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveStatus::Approved,
        ]);
    }

    /**
     * Set status to rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveStatus::Rejected,
        ]);
    }

    /**
     * Set status to cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Associate the leave request with a user in the employee role.
     */
    public function forEmployee(): static
    {
        return $this->state(function (array $attributes) {
            $user = User::factory()->create()->assignRole('employee');

            return ['user_id' => $user->id];
        });
    }

    /**
     * Associate the leave request with a user in the hr role.
     */
    public function forHr(): static
    {
        return $this->state(function (array $attributes) {
            $user = User::factory()->create()->assignRole('hr');

            return ['user_id' => $user->id];
        });
    }

    /**
     * Associate the leave request with a user in the admin role.
     */
    public function forAdmin(): static
    {
        return $this->state(function (array $attributes) {
            $user = User::factory()->create()->assignRole('admin');

            return ['user_id' => $user->id];
        });
    }
}
