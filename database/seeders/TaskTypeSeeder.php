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
        $task_types = [
            'Sweep Out',
            'Reclean Like F',
            'Reclean #1',
            'Pressure Wash Exterior',
            'Reclean #3',
            'Brush Machine',
            'Reclean #2',
            'Clean Rough',
            'Clean Rough #2',
            'Ct Clean',
            'Final Clean',
            'Floor Machine',
            'Floor Machine #2',
            'Frame Sweep',
            'Garage Clean Up',
            'Post Drywall Sweep',
            'Pre-Carpet',
            'Pressure Wash',
            'Pressure Wash #2',
            'Reclean #4',
            'Reclean Modelos',
            'Reclean Spec',
            'Rough Windows',
            'Steam Carpet',
            'Pw Fascia',
            'Yard Clean',
            'Weekley Specs',
            'Common Areas',
            'Corridors',
            'Storage Room',
            'Extra Clean Req',
            'Final Windows',
            'Only Soffit',
            'Stair Towers',
            'Parking Lot',
            'Lobby',
            'Elevator',
            'Pump And Trash Room',
            'Pw Exterior + All Flat Work',
            'Pw Parking Lot',
        ];

        foreach ($task_types as $task_type) {
            TaskType::updateOrCreate(
                ['name' => $task_type],
                [
                    'description' => $task_type,
                ]
            );
        }
    }
}
