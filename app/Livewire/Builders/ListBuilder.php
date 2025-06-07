<?php

namespace App\Livewire\Builders;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Schema;
use App\Models\Builder;


class ListBuilder extends Component
{
    use WithPagination;

    protected $listeners = [
        'contentUpdated' => '$refresh',
    ];

    public $sortBy = 'id';
    public $sortDirection = 'desc';
    public $columns = [];
    public $searchTerm = '';

    protected $listeners = [
        'search-tasks' => 'updateSearch',
    ];
    
    public function mount()
    {
        // Dynamically fetch column names from the database
        $this->columns = Schema::getColumnListing((new Builder)->getTable());

        // Filter out unwanted columns
        $this->columns = array_filter(
            Schema::getColumnListing((new Builder)->getTable()),
            fn ($column) => !in_array($column, ['id', 'created_at', 'updated_at'])
        );
        
    }

    public function sort($column)
    {
        if (in_array($column, $this->columns)) {
            if ($this->sortBy === $column) {
                $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                $this->sortBy = $column;
                $this->sortDirection = 'asc';
            }
        }
    }

    public function updateSearch($query)
    {
        $this->searchTerm = $query;
        $this->resetPage();
    }

    #[\Livewire\Attributes\Computed]
    public function getBuildersProperty()
    {
        if ($this->searchTerm !== '') {
            return Builder::search($this->searchTerm)->paginate(20);
        }

        return Builder::orderBy($this->sortBy, $this->sortDirection)
            ->paginate(20);
    }

    public function render()
    {
        return view('livewire.builders.list')
        ->layout('layouts.app', [
            'showAddNew' => true,
            'showSearch' => true,
            'currentAddable'  => 'builder',
        ]);
    }
}
