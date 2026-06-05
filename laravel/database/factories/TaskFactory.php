<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Task> */
class TaskFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title'       => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status'      => fake()->randomElement(TaskStatus::cases())->value,
            'priority'    => fake()->randomElement(TaskPriority::cases())->value,
            'due_date'    => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
        ];
    }
}
