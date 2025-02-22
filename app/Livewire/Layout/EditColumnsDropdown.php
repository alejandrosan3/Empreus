<?php

namespace App\Livewire\Layout;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

class EditColumnsDropdown extends Component
{
    public $columns= []	;

    #[On('update-column-lists')]
    public function updateColumnLists($fixedIds=[], $activeIds=[])
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
        Log::info('Merged:', ['Cols' => $this->columns,]);

        // Dispatch the updated version of the list
        $this->dispatch('column-list-updated', columns: $this->columns);

    }

    #[On('update-columns')]
    public function updateColumns($columns=[])
    {
        $this->columns = $columns;
    }


    public function render()
    {
        return view('livewire.layout.edit-columns-dropdown');
    }
}
