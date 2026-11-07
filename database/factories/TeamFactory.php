<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => fn () => tenancy()->divisionId() ?? Company::factory(),
            'name' => 'Crew '.$this->faker->unique()->numberBetween(1, 100000),
        ];
    }
}
