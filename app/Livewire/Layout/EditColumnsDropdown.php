<?php

namespace App\Livewire\Layout;

use Livewire\Component;
use Illuminate\Support\Arr;
use Livewire\Attributes\On;

class EditColumnsDropdown extends Component
{
    public $fixedColumns = [
        ['id' => 1, 'label' => 'Amount'],
    ];

    public $activeColumns = [
        ['id' => 2, 'label' => 'Refunded date', 'selected' => true],
        ['id' => 3, 'label' => 'Description', 'selected' => true],
        ['id' => 4, 'label' => 'Decline reason', 'selected' => false],
        ['id' => 5, 'label' => 'Customer', 'selected' => true],
        ['id' => 6, 'label' => 'Date', 'selected' => true],
        ['id' => 7, 'label' => 'Payment method', 'selected' => false],
    ];

    #[On('update-column-lists')]
    public function updateColumnLists(array $payload)
    {
        $targetGroup  = $payload['targetGroup'];
        $sourceGroup  = $payload['sourceGroup'];
        $reorderedIds = $payload['reorderedIds'];
        $movedId      = $payload['movedId'];

        // Grab the moved column
        $movedColumn = collect($sourceGroup === 'active' ? $this->activeColumns : $this->fixedColumns)
            ->firstWhere('id', $movedId);

        // Remove the column from the source group
        if ($sourceGroup === 'active') {
            $this->activeColumns = collect($this->activeColumns)
                ->reject(fn($col) => $col['id'] === $movedId)
                ->values()
                ->toArray();
        } else {
            $this->fixedColumns = collect($this->fixedColumns)
                ->reject(fn($col) => $col['id'] === $movedId)
                ->values()
                ->toArray();
        }

        // Insert the moved column into the target group in the correct order
        if ($targetGroup === 'active') {
            $this->activeColumns = collect($reorderedIds)
                ->map(fn($id) => $id == $movedId
                    ? $movedColumn
                    : Arr::first($this->activeColumns, fn($col) => $col['id'] == $id)
                )
                ->filter()
                ->values()
                ->toArray();
        } else {
            $this->fixedColumns = collect($reorderedIds)
                ->map(fn($id) => $id == $movedId
                    ? $movedColumn
                    : Arr::first($this->fixedColumns, fn($col) => $col['id'] == $id)
                )
                ->filter()
                ->values()
                ->toArray();
        }
    }

    public function render()
    {
        return view('livewire.layout.edit-columns-dropdown');
    }
}