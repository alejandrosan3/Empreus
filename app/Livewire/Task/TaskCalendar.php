<?php

namespace App\Livewire\Task;

use App\Models\Task;
use App\Traits\WithToast;
use Carbon\Carbon;
use Livewire\Component;

class TaskCalendar extends Component
{
    use WithToast;
    public array $weekDays = [];
    public array $tasksByDay = [];
    public $currentWeekStart;

    protected $listeners = ['taskDateUpdated'];

    protected $loadingStates = ['previousWeek', 'nextWeek', 'today', 'taskDateUpdated'];


    public function mount()
    {
        $this->currentWeekStart = Carbon::now()->startOfWeek();
        $this->loadWeekDays();
        $this->loadTasks();
    }

    public function previousWeek()
    {
        $this->currentWeekStart->subWeek();
        $this->loadWeekDays();
        $this->loadTasks();
    }

    public function nextWeek()
    {
        $this->currentWeekStart->addWeek();
        $this->loadWeekDays();
        $this->loadTasks();
    }

    public function today()
    {
        $this->currentWeekStart = Carbon::now()->startOfWeek();
        $this->loadWeekDays();
        $this->loadTasks();
    }

    #[Computed]
    public function isCurrentWeek(): bool
    {
        return $this->currentWeekStart->startOfWeek()->eq(Carbon::now()->startOfWeek());
    }

    private function loadWeekDays(): void
    {
        $this->weekDays = [];
        $today = Carbon::now()->format('Y-m-d');

        for ($i = 0; $i < 7; $i++) {
            $date = $this->currentWeekStart->copy()->addDays($i);
            $this->weekDays[] = [
                'date' => $date->format('Y-m-d'),
                'dayName' => $date->format('l'),
                'dayNumber' => $date->format('j'),
                'isToday' => $date->format('Y-m-d') === $today,
            ];
        }
    }

    private function loadTasks(): void
    {
        $this->tasksByDay = [];
        $startDate = $this->currentWeekStart;
        $endDate = $this->currentWeekStart->copy()->endOfWeek();

        $tasks = Task::query()
            ->select(
                'tasks.*',
                'lots.subdivision as subdivision',
                'lots.lot_number as lot_number',
                'lots.address as address',
                'task_types.name as task_type_name',
                'supervisors.name as supervisor_name',
                'builders.name as builder_name',
            )
            ->join('task_types', 'tasks.task_type_id', '=', 'task_types.id')
            ->join('lots', 'tasks.lot_id', '=', 'lots.id')
            ->join('supervisors', 'lots.supervisor_id', '=', 'supervisors.id')
            ->join('status_types', 'tasks.status_type_id', '=', 'status_types.id')
            ->join('builders', 'lots.builder_id', '=', 'builders.id')
            ->where('tasks.company_id', session('company_id'))
            ->whereIn('status_types.identifier', ['confirmed', 'in_progress'])
            ->whereBetween('tasks.scheduled_start_date', [$startDate, $endDate])
            ->orderBy('tasks.scheduled_start_date')
            ->get();

        // Group tasks by day
        foreach ($tasks as $task) {
            $dayKey = Carbon::parse($task->scheduled_start_date)->format('Y-m-d');
            $this->tasksByDay[$dayKey][] = $task;
        }
    }

    public function taskDateUpdated($taskId, $newDate)
    {
        try {
            $task = Task::find($taskId);
            if (!$task) {
                $this->showError('Task not found');
                return;
            }

            // Parse the new date and set it to start of day
            $newStartDate = Carbon::parse($newDate)->startOfDay();

            // Set the end date to the end of the same day
            $newEndDate = Carbon::parse($newDate)->endOfDay();

            // Update the task dates
            $task->scheduled_start_date = $newStartDate;
            $task->scheduled_end_date = $newEndDate;
            $task->save();

            $this->loadTasks();

            // Dispatch an event to reinitialize Sortable
            $this->dispatch('tasks-updated');

            $this->showSuccess('Task re-scheduled successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to update task date', [
                'task_id' => $taskId,
                'new_date' => $newDate,
                'error' => $e->getMessage()
            ]);
            $this->showError('Failed to re-schedule Task day.');
        }
    }


    public function render()
    {
        return view('livewire.task.task-calendar')->layout('layouts.app');
    }
}
