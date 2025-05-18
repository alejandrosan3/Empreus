<?php

namespace Database\Seeders;

use App\Models\TaskType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $task_types = \App\Enums\TaskType::all();

        foreach ($task_types as $task_type) {
            TaskType::findOrCreate(
                ['name' => $task_type],
                [
                    'description' => $task_type,
                ]
            );
        }
    }
}
