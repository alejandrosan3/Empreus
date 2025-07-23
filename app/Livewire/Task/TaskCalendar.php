<?php

namespace App\Livewire\Task;

use App\Models\Task;
use App\Models\TaskType;
use App\Models\Team;
use App\Traits\WithToast;
use Carbon\Carbon;
use Flux\DateRange;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Attributes\Computed;

class TaskCalendar extends Component
{
    use WithToast;
    public array $weekDays = [];
    public array $tasksByDay = [];
    public $currentRangeStart;
    public string $date;
    public $rangeDays = 7;

    #[Reactive]
    public DateRange $range;

    public array $columns = [];
    public array $selectedFilters = [];
    public array $filters = [];

    protected $listeners = ['taskDateUpdated'];

    protected $loadingStates = ['previousRange', 'nextRange', 'today', 'taskDateUpdated'];


    public function mount( DateRange $range )
    {

        $this->range = $range;
        $this->currentRangeStart = $this->range->copy()->start();

        $this->columns =
        [
            [
                'id' => 1,
                'label' => 'Task Type',
                'value' => 'task_type',
                'group' => 'fixed',
                'selected' => true,
                'filterable' => true,
                'filterType' => 'select',
                'color' => 'green',
            ],
            [
                'id' => 2,
                'label' => 'Team',
                'value' => 'team',
                'group' => 'active',
                'selected' => true,
                'filterable' => true,
                'filterType' => 'select',
                'color' => 'purple',
            ],
        ];

        $this->dispatch('update-columns', columns: $this->columns);
        $this->dispatchFilters();

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
        return $this->currentRangeStart->copy()->eq(Carbon::now()->startOfWeek());
       
    }

    private function loadRangeDays(): void
    {
        $this->weekDays = [];
        $today = Carbon::now()->format('Y-m-d');

        for ($i = 0; $i < $this->rangeDays; $i++) {
            $date = $this->currentRangeStart->copy()->addDays($i);

            $this->weekDays[] = [
                'date' => $date->format('Y-m-d'),
                'dayName' => $date->format('D'),
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

    // Triggered when the parent TaskScheduler component updates the range
    #[On('rangeChanged')]
    public function onUpdatedRange(DateRange $new)
    {
        $this->currentRangeStart = $this->range->copy()->start();
        $this->rangeDays = count($this->range->days()->toArray());
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
            ->when(!empty($this->selectedFilters['task_type']), function ($q) {
                $q->whereIn('tasks.task_type_id', $this->selectedFilters['task_type']);
            })
            ->when(!empty($this->selectedFilters['team']), function ($q) {
                $q->join('task_user', 'tasks.id', '=', 'task_user.task_id')
                  ->join('team_worker', 'task_user.user_id', '=', 'team_worker.worker_id')
                  ->whereIn('team_worker.team_id', $this->selectedFilters['team']);
            })
            ->orderBy('tasks.scheduled_start_date')
            ->get();

        // Group tasks by day
        foreach ($tasks as $task) {
            $dayKey = Carbon::parse($task->scheduled_start_date)->format('Y-m-d');
            $this->tasksByDay[$dayKey][] = $task;
        }
    }

        #[On('global-filters-updated')]
    public function handleGlobalFilters(array $filters)
    {
        $this->selectedFilters = $filters;
        $this->loadTasks();
        $this->dispatchFilters();
    }


    #[On('company-updated')]
    public function dispatchFilters()
    {
        $this->filters = [
            'task_type' => TaskType::orderBy('name')->get()->map(fn($t) => ['value' => $t->id, 'label' => $t->name])->toArray(),
            'team'      => Team::orderBy('name')->get()->map(fn($t) => ['value' => $t->id, 'label' => $t->name])->toArray(),
        ];

        $this->dispatch('filters-updated', filters: $this->filters);
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


    public function render()
    {
        return view('livewire.task.task-calendar')->layout('layouts.app', [
            'showFilters' => true,
            'currentAddable' => 'task',
        ]);
    }
}
