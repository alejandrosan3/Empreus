<?php
namespace App\Livewire\Pages;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Lot;


class ContentDetails extends Component
{
    public $standardTasks = [];
    public $extraTasks = [];

    public function mount()
    {
        // Placeholder data; replace with actual query logic
        $this->standardTasks = [
            [
                'builder' => 'Builder A',
                'subdivision' => 'Subdivision X',
                'lot' => '1234',
                'status' => 'in progress',
                'start_date' => '2025-01-01',
                'completed_date' => null,
            ],
            [
                'builder' => 'Builder B',
                'subdivision' => 'Subdivision Y',
                'lot' => '5678',
                'status' => 'completed',
                'start_date' => '2025-01-05',
                'completed_date' => '2025-01-10',
            ],
        ];

        $this->extraTasks = [
            [
                'builder' => 'Builder C',
                'subdivision' => 'Subdivision Z',
                'lot' => '91011',
                'status' => 'pending',
                'start_date' => null,
                'completed_date' => null,
            ],
            [
                'builder' => 'Builder D',
                'subdivision' => 'Subdivision W',
                'lot' => '121314',
                'status' => 'completed',
                'start_date' => '2024-12-20',
                'completed_date' => '2025-01-05',
            ],
        ];
    }

    public function render()
    {
        return view('livewire.pages.content-details')
        ->layout('layouts.app');
    }
}