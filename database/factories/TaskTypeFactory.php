<?php

namespace Database\Factories;

use App\Enums\TaskType as TaskTypeList;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskTypeFactory extends Factory
{
    public function definition(): array
    {
        $taskType = $this->faker->randomElement(TaskTypeList::all());

        return [
            'name' => $taskType,
            'description' => $taskType,
        ];
    }
}
