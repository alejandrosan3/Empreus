<?php

namespace App\Livewire\Task;

use Livewire\Component;

class TaskTeam extends Component
{
    public array $teams = [];

    public function mount(): void
    {
        $this->teams = [
            [
                'id' => 1,
                'name' => 'Team Alpha',
                'members' => ['Alice', 'Bob'],
                'tasks' => [
                    [
                        'id' => 101,
                        'title' => 'Prepare report',
                        'external' => [],
                    ],
                    [
                        'id' => 102,
                        'title' => 'Onboard client',
                        'external' => ['Charlie'],
                    ],
                ],
            ],
            [
                'id' => 2,
                'name' => 'Team Beta',
                'members' => ['Dave', 'Eve'],
                'tasks' => [
                    [
                        'id' => 201,
                        'title' => 'Update website',
                        'external' => [],
                    ],
                ],
            ],
            [
                'id' => null,
                'name' => 'No Team',
                'members' => [],
                'tasks' => [
                    [
                        'id' => 301,
                        'title' => 'Miscellaneous task',
                        'external' => [],
                    ],
                ],
            ],
        ];
    }

    public function render()
    {
        return view('livewire.task.task-team');
    }
}
