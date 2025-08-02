<?php

namespace App\Livewire\Teams;

use App\Models\Team;
use Livewire\Component;
use Livewire\WithPagination;

class ListTeam extends Component
{
    use WithPagination;

    public string $sortBy = 'id';
    public string $sortDirection = 'desc';
    public array $columns = ['name'];
    public string $searchTerm = '';

    protected $listeners = [
        'search-tasks'   => 'updateSearch',
        'contentUpdated' => '$refresh',
        'editField' => 'openEditModal',
    ];

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

    public function updateSearch($query): void
    {
        $this->searchTerm = $query;
        $this->resetPage();
    }

    #[\Livewire\Attributes\Computed]
    public function getTeamsProperty()
    {
        $query = Team::withCount('workers');

        if ($this->searchTerm !== '') {
            $query->where('name', 'like', '%'.$this->searchTerm.'%');
        }

        return $query->orderBy($this->sortBy, $this->sortDirection)
                     ->paginate(20);
    }

    public function render()
    {
        return view('livewire.teams.list-team')
        ->layout('layouts.app', [
                'showSearch' => true,
                'showAddNew' => true,
                'currentAddable'=> 'team',
            ]);
    }
}
