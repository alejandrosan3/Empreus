<?php

namespace Database\Factories;

use App\Models\Lot;
use App\Models\StatusType;
use App\Models\TaskType;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Builder>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lot_id' => Lot::factory(),
            'task_type_id' => TaskType::query()->inRandomOrder()->value('id') ?? TaskType::factory(),
            'account_id' => Account::factory(),
            'price' => $this->faker->randomFloat(2, 10, 200),
            'status_type_id' => StatusType::query()->inRandomOrder()->value('id') ?? StatusType::factory(),
            'scheduled_start_date' => $this->faker->dateTimeBetween('-1 month'),
            'scheduled_end_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'started_at' => $this->faker->optional()->dateTimeBetween('-1 month'),
            'ended_at' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'is_extra' => $this->faker->boolean(),
        ];
    }
}
