<?php

namespace App\Livewire\Task;

use App\Models\Task;
use App\Traits\WithToast;
use Carbon\Carbon;
use Livewire\Component;
use Flux\DateRange;

class TaskCalendar extends Component
{
    use WithToast;
    public array $weekDays = [];
    public array $tasksByDay = [];
    public $currentRangeStart;
    public string $date;
    public $rangeDays = 7;

    public DateRange $range;

    protected $listeners = ['taskDateUpdated'];

    protected $loadingStates = ['previousRange', 'nextRange', 'today', 'taskDateUpdated'];


    public function mount()
    {

        $this->range = DateRange::thisWeek();
        $this->currentRangeStart = $this->range->copy()->start();
        $this->loadRangeDays();
        $this->loadTasks();
    }

    protected function slideRange(int $direction): void
    {
        // direction = +1 for next, −1 for previous
        $this->currentRangeStart->addDays($direction * $this->rangeDays);

        $start = $this->currentRangeStart->copy();
        $end   = $start->copy()->addDays($this->rangeDays - 1);

        $this->range = new DateRange($start, $end);
    }

    public function previousRange()
    {
        $this->slideRange(-1);
        $this->loadRangeDays();
        $this->loadTasks();
    }

    public function nextRange()
    {
        $this->slideRange(1);
        $this->loadRangeDays();
        $this->loadTasks();
    }

    public function today()
    {
        // Resets this to the current weekly range
        $this->currentRangeStart = Carbon::now()->startOfWeek();
        $this->rangeDays= 7;

        $this->loadRangeDays();
        $this->loadTasks();
    }

    #[Computed]
    public function isCurrentRange(): bool
    {
        return $this->currentRangeStart->copy()->eq(Carbon::now());
    }

    private function loadRangeDays(): void
    {
        $this->weekDays = [];
        $today = Carbon::now()->format('Y-m-d');

        for ($i = 0; $i < $this->rangeDays; $i++) {
            $date = $this->currentRangeStart->copy()->addDays($i);

            $this->weekDays[] = [
                'date' => $date->format('Y-m-d'),
                'dayName' => $date->format('l'),
                'dayNumber' => $date->format('j'),
                'isToday' => $date->format('Y-m-d') === $today,
            ];
        }
    }

    // Triggers Whenever $range is updated by the date-picker
    public function updatedRange()
    {   
        
        // 1) Reset your “anchor” date
        $this->currentRangeStart = $this->range->copy()->start();

        // 3) Turn the period into an array of Carbon dates, then count them
        $this->rangeDays = count($this->range->days()->toArray());

        // 4) Rebuild
        $this->loadRangeDays();
        $this->loadTasks();
    }

    private function loadTasks(): void
    {
        $this->tasksByDay = [];
        $startDate = $this->range->copy()->start();
        $endDate   = $this->range->copy()->end();

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
