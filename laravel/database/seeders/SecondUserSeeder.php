<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class SecondUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'second@example.com'],
            [
                'name'     => 'Second User',
                'password' => 'password',
            ]
        );
        Task::factory()->count(3)->create(['user_id' => $user->id]);
    }
}