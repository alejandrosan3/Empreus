<?php

namespace App\Livewire\Subdivisions;

use Livewire\Attributes\On; 
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Schema;
use App\Models\Lot;
use App\Models\Subdivision;

class ListSubdivision extends Component
{
    use WithPagination;

    public int $companyId;

    public $sortBy = 'subdivision';
    public $sortDirection = 'desc';
    public array  $columns = ['subdivision'];

    public function mount()
    {
        $this->companyId   = session('company_id');

        // 1. Grab the table columns dynamically
        $this->columns = array_filter(
            Schema::getColumnListing((new Subdivision)->getTable()),
            fn ($column) => !in_array($column, ['id', 'created_at', 'updated_at'])
        );
    }

    public function sort(string $column): void
    {
        if (! in_array($column, $this->columns)) {
            return;
        }

        $this->sortDirection = $this->sortBy === $column
            ? ($this->sortDirection === 'asc' ? 'desc' : 'asc')
            : 'asc';

        $this->sortBy = $column;
    }

    #[\Livewire\Attributes\Computed]
    public function getSubdivisionsProperty()
    {
        return Lot::query()
            ->where('company_id', $this->companyId)
            ->whereNotNull('subdivision')
            ->where('subdivision', '!=', '')
            ->select('subdivision')
            ->distinct()
            ->orderBy($this->sortBy, $this->sortDirection)
            ->simplePaginate(20);
    }

     #[On('company-updated')]
    public function updateCompany(int $id): void
    {
        $this->companyId = $id;
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.subdivisions.list-subdivision')
        ->layout('layouts.app', [
            'showAddNew'     => true,
            'currentAddable' => 'subdivision',
        ]);
    }
}
