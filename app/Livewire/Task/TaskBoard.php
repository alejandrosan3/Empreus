<?php

namespace App\Livewire\Task;


use App\Traits\WithToast;
use Livewire\Component;
use Flux\DateRange;



class TaskBoard extends Component
{

    use WithToast;

    public array $tasks = [];
    public array $hasMore = [];
    public array $statuses = [];

    public DateRange $range;


    public function render()
    {
        return view('livewire.task.task-board')->layout('layouts.app');
    }
}
