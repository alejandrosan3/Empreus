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
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /**
         * Populate status types if empty
         */
        if (DB::table('status_types')->count() === 0) {
            $this->call(StatusTypeSeeder::class);
        }

        /**
         * Populate task types if empty
         */
        if (DB::table('task_types')->count() === 0) {
            $this->call(TaskTypeSeeder::class);
        }

        /**
         * Populate accounts table if empty
         */
        if (DB::table('companies')->count() === 0) {
            $this->call(CompanySeeder::class);
        }

        /**
         * Populate users table if empty
         */
        if (DB::table('users')->count() === 0) {
            User::factory()->create([
                'name' => 'Demo User',
                'email' => 'demo@demo.com',
                'password' => Hash::make('demo'),
            ]);
        }

        if (DB::table('company_users')->count() === 0) {
            $this->call(CompanyUserSeeder::class);
        }
        /*
        Builder::factory(50)->create();

        Supervisor::factory(50)->create();

        Lot::factory(50)->create();

        Task::factory(100)->create();

        TaskNote::factory(10)->create(); */
    }
}
