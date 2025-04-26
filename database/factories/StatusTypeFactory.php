<?php

namespace Database\Factories;

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
        return [
            'identifier' => 'pending',
            'name' => 'Pending',
            'description' => 'The order has been added but no work has started yet.',
            'color' => '#B0BEC5',
            'color_dark' => '#757575',
        ];
    }
}
