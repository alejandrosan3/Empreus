<?php

namespace App\Livewire\Task;


use App\Models\Task;
use App\Traits\WithToast;
use Livewire\Component;
use Flux\DateRange;



class TaskBoard extends Component
{

    use WithToast;

    public array $tasks = [];
    public array $hasMore = [];
    public array $statuses = [];
    public ?string $currentNote = null;

    public DateRange $range;
    public array $howManyMore = [];


    public function showLatestNote(int $taskId): void
    {
        $this->reset('currentNote');
        $task = Task::withExists(['taskNotes' => fn ($q) => $q->latest()])->find($taskId);

        if ($task?->taskNotes->isNotEmpty()) {
            $this->currentNote   = $task->taskNotes->first()->description;
        }
    }

    public function render()
    {
        return view('livewire.task.task-board')->layout('layouts.app');
    }
}
