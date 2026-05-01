<?php

namespace Database\Factories;

use App\Models\Todo;
use App\Models\User;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Todo>
 */
class TodoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => TaskStatus::Pending,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => TaskStatus::InProgress]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => TaskStatus::Completed]);
    }
}
