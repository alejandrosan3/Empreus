<?php

namespace App\Livewire\Supervisors;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Schema;
use App\Models\Supervisor;

class ListSupervisor extends Component
{
    use WithPagination;

    protected $listeners = [
        'contentUpdated' => '$refresh',
    ];

    public int    $companyId;

    public array  $columns       = [];
    public string $sortBy        = 'id';
    public string $sortDirection = 'desc';


    public function mount(): void
    {
        $this->companyId = session('company_id');

        // fetch all table columns, then drop the ones we don’t show
        $this->columns = array_filter(
            Schema::getColumnListing((new Supervisor)->getTable()),
            fn ($column) => ! in_array($column, ['id', 'created_at', 'updated_at'])
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
    public function getSupervisorsProperty()
    {
        return Supervisor::query()
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
        return view('livewire.supervisors.list-supervisor')
            ->layout('layouts.app', [
                'showAddNew'     => true,
                'currentAddable' => 'supervisor',
            ]);
    }
}
