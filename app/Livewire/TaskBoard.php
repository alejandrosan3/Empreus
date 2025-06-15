<?php

namespace App\Livewire;

use App\Models\StatusType;
use App\Models\Task;
use App\Traits\WithToast;
use Livewire\Component;
use Flux\Flux;
use Mockery\Exception;

class TaskBoard extends Component
{

    use WithToast;

    public array $tasks = [];

    public array $tasksById = [];


    public array $statuses = [
        'confirmed' => 'Confirmed',
        'in_progress' => 'In Progress',
        'payment_pending' => 'Payment Pending',
        'completed' => 'Completed',
        'blocked' => 'Blocked',
    ];

    public function getStatuses(): array
    {
        return [
            \App\Enums\StatusType::CONFIRMED->value => 'Confirmed',
            \App\Enums\StatusType::IN_PROGRESS->value => 'In Progress',
            \App\Enums\StatusType::PAYMENT_PENDING->value => 'Payment Pending',
            \App\Enums\StatusType::COMPLETED->value => 'Completed',
            \App\Enums\StatusType::BLOCKED->value => 'Blocked',
        ];
    }


    public function loadTasks(): void
    {
        $this->tasks['confirmed'] = Task::getTasksByStatusForCurrentCompany('confirmed', 50);
        $this->tasks['in_progress'] = Task::getTasksByStatusForCurrentCompany('in_progress', 50);
        $this->tasks['completed'] = Task::getTasksByStatusForCurrentCompany('completed', 50);
        $this->tasks['payment_pending'] = Task::getTasksByStatusForCurrentCompany('payment_pending', 50);
        $this->tasks['blocked'] = Task::getTasksByStatusForCurrentCompany('blocked', 50);

        foreach ($this->tasks as $taskType => $tasks) {
            foreach ($tasks as $task) {
                $this->tasksById[$task->id] = $task;
            }
        }
    }

    public function updateTaskStatus(int $taskId, string $newStatus)
    {
        if (!isset($this->tasksById[$taskId])) {
            $this->showError('Task not found.');
            return;
        }

        try {
            $statusType = StatusType::where('identifier', $newStatus)->first();

            if (!$statusType) {
                $this->showError('Invalid status type.');
                return;
            }

            $task = $this->tasksById[$taskId];
            $task->update(['status_type_id' => $statusType->id]);

            $this->loadTasks();
            $this->showSuccess('Task status updated successfully.');
            
        } catch (Exception $e) {
            \Log::error('Failed to update task status', [
                'task_id' => $taskId,
                'new_status' => $newStatus,
                'error' => $e->getMessage()
            ]);
            $this->showError('Failed to update task status.');
        }

    }

    public function mount()
    {
        $this->loadTasks();
    }

    public function render()
    {
        return view('livewire.task-board')->layout('layouts.app');
    }
}
