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
                'color' => 'zinc', // Light mode: Neutral gray
                'color_dark' => 'zinc', // Darker slate for dark mode
            ],
            [
                'identifier' => 'confirmed',
                'name' => 'Confirmed',
                'description' => 'The task is confirmed with a precise date (important for scheduling).',
                'color' => 'amber', // Livewire-compatible orange
                'color_dark' => 'amber', // Darker shade for contrast in dark mode
            ],
            [
                'identifier' => 'in_progress',
                'name' => 'In Progress',
                'description' => 'The task is scheduled and being worked on.',
                'color' => 'yellow', // Yellow with good readability
                'color_dark' => 'yellow', // Slightly darker for contrast
            ],
            [
                'identifier' => 'completed',
                'name' => 'Completed',
                'description' => 'The task has been successfully completed.',
                'color' => 'green', // Standard Tailwind green
                'color_dark' => 'green', // Darker green for contrast
            ],
            [
                'identifier' => 'blocked',
                'name' => 'Blocked',
                'description' => 'The task cannot proceed due to external factors.',
                'color' => 'blue', // Standard Tailwind blue
                'color_dark' => 'blue', // Darker shade for dark mode
            ],
            [
                'identifier' => 'payment_pending',
                'name' => 'Payment Pending',
                'description' => 'Payment is required but not yet received.',
                'color' => 'purple', // Tailwind-compatible purple
                'color_dark' => 'purple', // Darker purple for readability
            ],
            [
                'identifier' => 'cancelled',
                'name' => 'Cancelled',
                'description' => 'The task was cancelled or voided.',
                'color' => 'red', // Standard Tailwind red
                'color_dark' => 'red', // Darker red for better contrast
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
