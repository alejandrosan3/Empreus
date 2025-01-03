<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\StatusType;

class StatusTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'identifier' => 'pending',
                'name' => 'Pending',
                'description' => 'The order has been added but no work has started yet.',
                'color' => '#B0BEC5',
                'color_dark' => '#757575',
            ],
            [
                'identifier' => 'confirmed',
                'name' => 'Confirmed',
                'description' => 'The task is confirmed with a precise date (important for scheduling).',
                'color' => '#FF9800',
                'color_dark' => '#F57C00',
            ],
            [
                'identifier' => 'in_progress',
                'name' => 'In Progress',
                'description' => 'The task is scheduled and being worked on.',
                'color' => '#FFEB3B',
                'color_dark' => '#FFC107',
            ],
            [
                'identifier' => 'completed',
                'name' => 'Completed',
                'description' => 'The task has been successfully completed.',
                'color' => '#4CAF50',
                'color_dark' => '#388E3C',
            ],
            [
                'identifier' => 'blocked',
                'name' => 'Blocked',
                'description' => 'The task cannot proceed due to external factors.',
                'color' => '#2196F3',
                'color_dark' => '#1E88E5',
            ],
            [
                'identifier' => 'payment_pending',
                'name' => 'Payment Pending',
                'description' => 'Payment is required but not yet received.',
                'color' => '#AB47BC',
                'color_dark' => '#8E24AA',
            ],
            [
                'identifier' => 'cancelled',
                'name' => 'Payment Pending',
                'description' => ' The task was cancelled or voided.',
                'color' => '#F44336',
                'color_dark' => '#F44336',
            ],
        ];

        foreach ($statuses as $status) {
            StatusType::updateOrCreate(
                ['identifier' => $status['identifier']],
                $status
            );
        }
    }
}
