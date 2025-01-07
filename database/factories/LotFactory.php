<?php

namespace Database\Factories;

use App\Models\Builder;
use App\Models\StatusType;
use App\Models\Supervisor;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Builder>
 */
class LotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'builder_id' => Builder::factory(),
            'supervisor_id' => Supervisor::factory(),
            'account_id' => Account::query()->inRandomOrder()->value('id') ?? Account::factory(),
            'lot_number' => $this->faker->unique()->numberBetween(1, 1000),
            'phase' => $this->faker->randomDigitNotZero() . $this->faker->randomLetter(),
            'status_type_id' => StatusType::query()->inRandomOrder()->value('id') ?? StatusType::factory(),
            'address' => $this->faker->address(),
            'subdivision' => $this->faker->address(),
            'floor_area' => $this->faker->randomFloat(2, 100, 200),
            'num_of_floors' => $this->faker->numberBetween(1, 2),
        ];
    }
}
