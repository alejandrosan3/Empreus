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
    public array  $columns = [];
    public $searchTerm = '';

    protected $listeners = [
        'search-tasks' => 'updateSearch',
    ];

    public function mount()
    {
        $this->companyId   = session('company_id');
        $this->columns   = ['subdivision'];

        // 1. Grab the table columns dynamically
        $this->columns = array_filter(
            Schema::getColumnListing((new Subdivision)->getTable()),
            fn ($column) => !in_array($column, ['id', 'created_at', 'updated_at'])
        );

        // ensure the displayed column is allowed for sorting
        if (! in_array('subdivision', $this->columns)) {
            $this->columns[] = 'subdivision';
        }
    }

    public function sort(string $column)
    {
        if (! in_array($column, $this->columns)) {
            return;
        }

        $this->sortDirection = $this->sortBy === $column
            ? ($this->sortDirection === 'asc' ? 'desc' : 'asc')
            : 'asc';

        $this->sortBy = $column;
    }

    public function updateSearch($query)
    {
        $this->searchTerm = $query;
        $this->resetPage();
    }

    #[\Livewire\Attributes\Computed]
    public function getSubdivisionsProperty()
    {
        $query = Lot::query()
            ->where('company_id', $this->companyId)
            ->whereNotNull('subdivision')
            ->where('subdivision', '!=', '')
            ->select('subdivision')
            ->distinct();

        if ($this->searchTerm !== '') {
            $ids = Lot::search($this->searchTerm)->keys();
            $query->whereIn('id', $ids);

            // Preserve relevance order
            $query->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')');
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        return $query->paginate(20);
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
            'showSearch'     => true,
            'currentAddable' => 'subdivision',
        ]);
    }
}
