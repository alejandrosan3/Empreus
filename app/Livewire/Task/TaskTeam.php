<?php

namespace App\Livewire\Task;

use Flux\DateRange;
use Livewire\Component;

class TaskTeam extends Component
{
    public DateRange $range;

    /** @var array<int,array{id:int,name:string,members:array}> */
    public array $teams = [];

    /**
     * tasksByTeam keys are team_id or "none", values are arrays of task DTOs
     * [
     *    'none' => [ ['id'=>..,'title'=>..,'subdivision'=>..,'lot_number'=>..,'address'=>..,'date'=>..], ... ],
     *    1 => [ ... ],
     * ]
     * @var array<string|int,array<int,array<string,mixed>>>
     */
    public array $tasksByTeam = [];

    public function render()
    {
        return view('livewire.task.task-team');
    }
}
