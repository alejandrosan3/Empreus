<?php

namespace App\Livewire\Partials;

use Livewire\Component;



class TaskNotes extends Component
{
    public $tasks;

    // Sort-related
    public $sortBy = 'task_notes.created_at';
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

    protected function sortNotes($notes)
    {
        // If nothing to sort by, return as-is
        if (!$this->sortBy) {
            return $notes;
        }

        // Handle relationship-based columns
        // For example, "task_type.name" means we want to sort by $note->task->taskType->name.
        if ($this->sortBy === 'task_type.name') {
            return $this->sortDirection === 'asc'
                ? $notes->sortBy(fn($note) => $note->task?->taskType?->name ?? '')
                : $notes->sortByDesc(fn($note) => $note->task?->taskType?->name ?? '');
        }

        // Or if the user clicks "task_notes.created_at", that really means $note->created_at
        if ($this->sortBy === 'task_notes.created_at') {
            return $this->sortDirection === 'asc'
                ? $notes->sortBy(fn($note) => $note->created_at)
                : $notes->sortByDesc(fn($note) => $note->created_at);
        }

        // Default (no recognized column), just return
        return $notes;
    }

    public function mount($tasks)
    {
        $this->tasks = $tasks;
    }
    public function render()
    {
         // 1. Flatten & filter
        $allNotes = $this->tasks
        ->flatMap->taskNotes
        ->filter(fn($note) => !empty($note->description));


        $sortedNotes = $this->sortNotes($allNotes);

        return view('livewire.partials.task-notes', [
            'allNotes' => $sortedNotes,
        ]);
    }
}
