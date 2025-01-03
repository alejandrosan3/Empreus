<?php

namespace Database\Seeders;

use App\Models\Lot;
use App\Models\Supervisor;
use App\Models\Task;
use App\Models\TaskNote;
use App\Models\User;
use App\Models\Builder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@demo.com',
            'password' => Hash::make('demo'),
        ]);

        Builder::factory(50)->create();

        Supervisor::factory(50)->create();

        Lot::factory(50)->create();

        Task::factory(100)->create();

        TaskNote::factory(10)->create();
    }
}
