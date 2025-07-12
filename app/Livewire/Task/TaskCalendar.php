<?php

namespace App\Livewire\Task;

use App\Models\Task;
use App\Traits\WithToast;
use Carbon\Carbon;
use Flux\DateRange;
use Livewire\Component;

class TaskCalendar extends Component
{
    use WithToast;
    public array $weekDays = [];
    public array $tasksByDay = [];
    public $currentWeekStart;
    public DateRange $range;
    public string $date;
    public int $rangeDays = 7;

    protected $listeners = ['taskDateUpdated'];

    protected $loadingStates = ['previousWeek', 'nextWeek', 'today', 'taskDateUpdated'];


    public function mount()
    {
        $this->date = Carbon::now()->format('Y-m-d');
        $this->currentWeekStart = Carbon::parse($this->date)->startOfDay();
        $this->range = new DateRange($this->currentWeekStart->copy(), $this->currentWeekStart->copy()->addDays($this->rangeDays - 1));
        $this->rangeDays = $this->range->start()->diffInDays($this->range->end()) + 1;
        $this->loadWeekDays();
        $this->loadTasks();
    }

    public function previousWeek()
    {
        $this->currentWeekStart->subDays($this->rangeDays);
        $this->date = $this->currentWeekStart->format('Y-m-d');
        $this->range = new DateRange($this->currentWeekStart->copy(), $this->currentWeekStart->copy()->addDays($this->rangeDays - 1));
        $this->loadWeekDays();
        $this->loadTasks();
    }

    public function nextWeek()
    {
        $this->currentWeekStart->addDays($this->rangeDays);
        $this->date = $this->currentWeekStart->format('Y-m-d');
        $this->range = new DateRange($this->currentWeekStart->copy(), $this->currentWeekStart->copy()->addDays($this->rangeDays - 1));
        $this->loadWeekDays();
        $this->loadTasks();
    }

    public function today()
    {
        $this->currentWeekStart = Carbon::now()->startOfDay();
        $this->date = $this->currentWeekStart->format('Y-m-d');
        $this->range = new DateRange($this->currentWeekStart->copy(), $this->currentWeekStart->copy()->addDays($this->rangeDays - 1));
        $this->loadWeekDays();
        $this->loadTasks();
    }

    #[Computed]
    public function isCurrentWeek(): bool
    {
        return $this->currentWeekStart->startOfDay()->eq(Carbon::now()->startOfDay());
    }

    private function loadWeekDays(): void
    {
        $this->weekDays = [];
        $today = Carbon::now()->format('Y-m-d');

        for ($i = 0; $i < $this->rangeDays; $i++) {
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
        $startDate = $this->currentWeekStart->copy()->startOfDay();
        $endDate = $this->currentWeekStart->copy()->addDays($this->rangeDays - 1)->endOfDay();

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

    public function updatedDate()
    {
        $this->currentWeekStart = Carbon::parse($this->date)->startOfDay();
        $this->range = new DateRange($this->currentWeekStart->copy(), $this->currentWeekStart->copy()->addDays($this->rangeDays - 1));
        $this->loadWeekDays();
        $this->loadTasks();
    }

    public function updatedRange($value)
    {
        if ($value instanceof DateRange) {
            $range = $value;
        } elseif (is_string($value)) {
            [$start, $end] = array_pad(explode('/', $value), 2, null);
            $start = Carbon::parse($start);
            $end = Carbon::parse($end ?? $start);
            $range = new DateRange($start, $end);
        } elseif (is_array($value) && isset($value['start'], $value['end'])) {
            $range = new DateRange(Carbon::parse($value['start']), Carbon::parse($value['end']));
        } else {
            return;
        }

        $this->currentWeekStart = $range->start()->copy();
        $this->rangeDays = $range->start()->diffInDays($range->end()) + 1;
        $this->date = $this->currentWeekStart->format('Y-m-d');
        $this->range = $range;
        $this->loadWeekDays();
        $this->loadTasks();
    }


    public function render()
    {
        return view('livewire.task.task-calendar')->layout('layouts.app');
    }
}
