<?php

namespace App\Livewire\Lot;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Lot;

class LotProgress extends Component
{
    use WithPagination;

    public $sortBy = 'id';
    public $sortDirection = 'desc';
    public $columns = [];
    public $taskTypes = ['Clean Rough', 'Pressure Wash', 'Floor Machine', 'Reclean #1', 'Reclean #2'];

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[\Livewire\Attributes\Computed]
    public function getLotsProperty()
    {
        return Auth::user()
            ->lotsForCurrentCompany()
            ->tap(function ($query) {
                if ($this->sortBy === 'builder') {
                    $query->join('builders', 'lots.builder_id', '=', 'builders.id')
                          ->orderBy('builders.name', $this->sortDirection);
                } elseif ($this->sortBy === 'status') {
                    $query->join('status_types', 'lots.status_type_id', '=', 'status_types.id')
                          ->orderBy('status_types.name', $this->sortDirection);
                } else {
                    $query->orderBy($this->sortBy, $this->sortDirection);
                }
            })
            ->paginate(10);
    }

   
    public function render()
    {

        return view('livewire.lot.lot-progress', [
            'lots' => $this->lots,
        ])->layout('layouts.app');
    }


}
