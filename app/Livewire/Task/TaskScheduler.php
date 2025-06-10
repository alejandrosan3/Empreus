<?php

namespace App\Livewire\Task;

use Livewire\Component;
use App\Models\Task;
use App\Models\StatusType;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

class TaskScheduler extends Component
{
    public $statuses;

    public function mount()
    {
        $this->statuses = StatusType::orderBy('id')->get();
    }

    #[Computed]
    public function tasksByStatus()
    {
        return Auth::user()
            ->tasksForCurrentCompany()
            ->with(['taskType', 'lot'])
            ->get()
            ->groupBy('status_type_id');
    }

    #[On('task-moved')]
    public function updateTaskStatus(int $taskId, int $statusId): void
    {
        Task::find($taskId)?->update(['status_type_id' => $statusId]);
    }

    public function render()
    {
        return view('livewire.task.task-scheduler', [
            'groupedTasks' => $this->tasksByStatus,
        ])->layout('layouts.app');
    }
}
