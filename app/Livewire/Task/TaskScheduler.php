<?php

namespace App\Livewire\Task;

use Livewire\Component;

class TaskScheduler extends Component
{
    public function render()
    {
        return view('livewire.task.task-scheduler')
            ->layout('layouts.app');
    }
}
