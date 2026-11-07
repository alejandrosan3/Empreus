<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    public function definition(): array
    {
        return [
            'business_id' => fn () => tenancy()->businessId() ?? Business::factory(),
            'name' => $this->faker->unique()->word(),
            'color' => $this->faker->hexColor(),
        ];
    }
}
