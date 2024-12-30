<?php

namespace App\Livewire\Lot;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Schema;

class LotProgress extends Component
{
    public $dummyData = [
        [
            'id' => 1, 'team' => 'Pristine Pathways', 'builder' => 'DRH', 'subdivision' => 'BayPointe',
            'field_workers' => 18, 'lot' => 18,
            'tasks' => [
                'Re Clean' => true,
                'Floor Clean' => false,
                'Final Wind' => false,
                'PW' => true,
            ],
            'floor' => 1,
            'progress' => 'Completed', 'progress_color' => 'lime', 'EPO' => "-"
        ],
        // Add other rows following this structure
    ];
    
    public $tasks = ['Re Clean', 'Floor Clean', 'Final Wind', 'PW'];

    public function render()
    {
        return view('livewire.lot.lot-progress', [
            'dummyData' => $this->dummyData,
            'tasks' => $this->tasks,
        ])->layout('layouts.app');
    }


}
