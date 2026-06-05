<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class LargeTaskSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstWhere('email', 'test@example.com');

        $count      = 1000;
        $statuses   = TaskStatus::cases();
        $priorities = TaskPriority::cases();

        for ($i = 1; $i <= $count; $i++) {
            Task::create([
                'user_id'     => $user->id,
                'title'       => "this is title {$i}",
                'description' => "this is description {$i}",
                'status'      => $statuses[array_rand($statuses)],
                'priority'    => $priorities[array_rand($priorities)],
                'due_date'    => now()->addDays(rand(1, 365))->toDateString(),
            ]);
        }
    }
}