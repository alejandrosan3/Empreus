<?php

namespace App\Livewire\Workers;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class ListWorker extends Component
{
    use WithPagination;

    public string $sortBy = 'id';
    public string $sortDirection = 'desc';
    public array $columns = ['name', 'email'];
    public string $searchTerm = '';

    protected $listeners = [
        'search-tasks'   => 'updateSearch',
        'contentUpdated' => '$refresh',
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
    public function getWorkersProperty()
    {
        $query = User::where('role', 'worker');

        if ($this->searchTerm !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->searchTerm.'%')
                  ->orWhere('email', 'like', '%'.$this->searchTerm.'%');
            });
        }

        return $query->orderBy($this->sortBy, $this->sortDirection)
                     ->paginate(20);
    }

    public function render()
    {
        return view('livewire.workers.list-worker')
            ->layout('layouts.app', [
                'showSearch' => true,
                'showAddNew' => true,
                'currentAddable'=> 'worker',
            ]);
    }
}
