<?php

namespace Database\Factories;

use App\Enums\StatusType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StatusType>
 */
class StatusTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statusType = $this->faker->randomElement(StatusType::all());

        return [
            'identifier' => $statusType,
            'name' => $statusType,
            'description' => $this->faker->sentence,
            'color' => '#B0BEC5',
            'color_dark' => '#757575',
        ];
    }
}
