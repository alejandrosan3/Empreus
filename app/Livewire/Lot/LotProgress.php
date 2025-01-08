<?php

namespace App\Livewire\Lot;

use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Schema;

class LotProgress extends Component
{
    public $taskTypes = ['Clean Rough', 'Pressure Wash', 'Floor Machine', 'Reclean #1', 'Reclean #2'];

    public function render()
    {
        $lots = Auth::user()->lotsForCurrentCompany()->get();

        return view('livewire.lot.lot-progress', [
            'lots' => $lots,
        ])->layout('layouts.app');
    }


}
