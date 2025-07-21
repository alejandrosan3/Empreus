<?php

namespace App\Livewire;

use App\Models\StatusType;
use App\Models\Task;
use App\Traits\WithToast;
use Livewire\Component;
use Flux\Flux;
use Flux\DateRange;
use Mockery\Exception;

class TaskBoard extends Component
{

    use WithToast;

    public array $tasks = [];

    public array $tasksById = [];

    public array $loadedCounts = [];

    public array $hasMore = [];

    public DateRange $range;


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
        foreach (array_keys($this->getStatuses()) as $status) {
            $limit = $this->loadedCounts[$status] ?? 5;
            $tasks = Task::getTasksByStatusForCurrentCompany($status, $limit, 0, $this->range);
            $this->tasks[$status] = $tasks;
            $this->loadedCounts[$status] = $tasks->count();
            $this->hasMore[$status] = $tasks->count() === $limit;

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

    public function loadMore(string $status, int $count = 15): void
    {
        $offset = $this->loadedCounts[$status] ?? 0;
        $tasks = Task::getTasksByStatusForCurrentCompany($status, $count, $offset, $this->range);

        foreach ($tasks as $task) {
            $this->tasks[$status][] = $task;
            $this->tasksById[$task->id] = $task;
        }

        $this->loadedCounts[$status] = ($this->loadedCounts[$status] ?? 0) + $tasks->count();
        $this->hasMore[$status] = $tasks->count() === $count;
    }

    public function updatedRange()
    {
        foreach (array_keys($this->getStatuses()) as $status) {
            $this->tasks[$status] = [];
            $this->loadedCounts[$status] = 0;
            $this->hasMore[$status] = true;
        }

        foreach (array_keys($this->getStatuses()) as $status) {
            $this->loadMore($status, 5);
        }
    }


    public function mount(DateRange $range)
    {
        $this->range = $range;
        foreach (array_keys($this->getStatuses()) as $status) {
            $this->tasks[$status] = [];
            $this->loadedCounts[$status] = 0;
            $this->hasMore[$status] = true;
        }

        foreach (array_keys($this->getStatuses()) as $status) {
            $this->loadMore($status, 5);
        }
    }

    public function render()
    {
        return view('livewire.task-board')->layout('layouts.app');
    }
}
