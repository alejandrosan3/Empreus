<?php

namespace Database\Factories;

use App\Enums\TaskType as TaskTypeList;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskTypeFactory extends Factory
{
    public function definition(): array
    {
        $taskType = $this->faker->randomElement(TaskTypeList::all());

        return [
            'business_id' => fn () => tenancy()->businessId() ?? Business::factory(),
            'name' => $taskType,
            'description' => $taskType,
        ];
    }
}
