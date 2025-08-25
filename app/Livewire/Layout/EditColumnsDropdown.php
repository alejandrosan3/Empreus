<?php

namespace App\Livewire\Layout;

use App\Services\ColumnPreferenceService;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Modelable;

class EditColumnsDropdown extends Component
{
    #[Modelable]
    public $columns= []	;
    protected ColumnPreferenceService $columnPreferences;


    #[On('update-column-lists')]
    public function updateColumnLists($fixedIds=[], $activeIds=[], string $context='')
    {
        // Reorder and update group for fixed columns:
        $orderedFixed = collect($fixedIds)->map(function ($id) {
            $column = collect($this->columns)->firstWhere('id', (int)$id);
            if ($column) {
                $column['group'] = 'fixed';
                $column['selected'] = true;
            }
            return $column;
        })->filter()->values()->toArray();

        // Reorder and update group for active columns:
        $orderedActive = collect($activeIds)->map(function ($id) {
            $column = collect($this->columns)->firstWhere('id', (int)$id);
            if ($column) {
                $column['group'] = 'active';

            }
            return $column;
        })->filter()->values()->toArray();

        // Merge back the two groups.
        $this->columns = array_merge($orderedFixed, $orderedActive);

        // Dispatch the updated version of the list
        $this->dispatch('column-list-updated', columns: $this->columns);

    }

    #[On('update-columns')]
    public function updateColumns($columns=[])
    {
        $this->columns = $columns;
    }

    public function boot(ColumnPreferenceService $svc) // <-- add this
    {
        $this->columnPreferences = $svc;
    }
    public function render()
    {
        return view('livewire.layout.edit-columns-dropdown');
    }
}
