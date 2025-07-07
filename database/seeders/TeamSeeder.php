<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create teams
        $teams = [
            [
                'name' => 'Plumbing Team',
                'description' => 'Specialized team for plumbing installations and repairs'
            ],
            [
                'name' => 'Electrical Team',
                'description' => 'Team focused on electrical work and installations'
            ],
            [
                'name' => 'Framing Team',
                'description' => 'Team specialized in framing and structural work'
            ],
            [
                'name' => 'Roofing Team',
                'description' => 'Team dedicated to roofing installations and repairs'
            ],
            [
                'name' => 'Flooring Team',
                'description' => 'Team specialized in flooring installations'
            ],
            [
                'name' => 'Finishing Team',
                'description' => 'Team focused on finishing work and final touches'
            ],
        ];

        foreach ($teams as $teamData) {
            Team::create($teamData);
        }
    }
}
