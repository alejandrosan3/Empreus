<?php

namespace App\Livewire\Partials;

use Livewire\Component;

class TaskNotes extends Component
{
    public $tasks;

    public function mount($tasks)
    {
        $this->tasks = $tasks;
    }
    public function render()
    {
        return view('livewire.partials.task-notes');
    }
}
