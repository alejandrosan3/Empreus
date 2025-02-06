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
            'Clean Rough #3',
            'Clean Rough #4',
            'Clean Rough #5',
            'Clean Rough #6',
            'Clean Rough #7',
            'Clean Rough #8',
            'Clean Rough #9',
            'Clean Rough #10',
            'Ct Clean',
            'Final Clean',
            'Floor Machine',
            'Floor Machine #2',
            'Floor Machine #3',
            'Floor Machine #4',
            'Floor Machine #5',
            'Floor Machine #6',
            'Floor Machine #7',
            'Floor Machine #8',
            'Floor Machine #9',
            'Floor Machine #10',
            'Frame Sweep',
            'Garage Clean Up',
            'Post Drywall Sweep',
            'Pre-Carpet',
            'Pressure Wash',
            'Pressure Wash #2',
            'Pressure Wash #3',
            'Pressure Wash #4',
            'Pressure Wash #5',
            'Pressure Wash #6',
            'Pressure Wash #7',
            'Pressure Wash #8',
            'Pressure Wash #9',
            'Pressure Wash #10',
            'Reclean #4',
            'Reclean #5',
            'Reclean #6',
            'Reclean #7',
            'Reclean #8',
            'Reclean #9',
            'Reclean #10',
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
            'Warranty Clean',
            'QA Clean',
            'Reclean Modelo',
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
