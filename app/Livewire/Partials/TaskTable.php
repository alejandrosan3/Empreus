<?php

namespace App\Livewire\Partials;

use Livewire\Component;

class TaskTable extends Component
{
    public $title;
    public $badgeLabel;
    public $badgeColor;
    public $headers = [];
    public $taskGroup = [];

    // Sort-related
    public $sortBy = 'ended_at';
    public $sortDirection = 'desc';


    /**
    * Sort the table by a given column, toggling sort direction if needed,
    * then recalc filter dropdowns so the user sees updated lists.
    */
    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    protected function sortTasks($collection)
    {
        // If no sortBy specified, just return as is:
        if (! $this->sortBy) {
            return $collection;
        }


        if ($this->sortBy === 'status_type.name') {
            // Sort by the related statusType’s name
            return $this->sortDirection === 'asc'
                ? $collection->sortBy(fn($task) => $task->statusType->name ?? '')
                : $collection->sortByDesc(fn($task) => $task->statusType->name ?? '');
        } elseif ($this->sortBy === 'task_type.name') {
            // Sort by the related taskType’s name
            return $this->sortDirection === 'asc'
                ? $collection->sortBy(fn($task) => $task->taskType->name ?? '')
                : $collection->sortByDesc(fn($task) => $task->taskType->name ?? '');
        }

        // Otherwise, assume it's a direct attribute like "scheduled_start_date", "ended_at", etc.
        return $this->sortDirection === 'asc'
            ? $collection->sortBy($this->sortBy)
            : $collection->sortByDesc($this->sortBy);
    }


    public function render()
    {
        $sortedGroup = $this->sortTasks(collect($this->taskGroup));

        return view('livewire.partials.task-table', [
            'sortedGroup' => $sortedGroup,
        ]);
    }
}
