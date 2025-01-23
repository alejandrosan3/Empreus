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

    public function render()
    {
        return view('livewire.partials.task-table');
    }
}
