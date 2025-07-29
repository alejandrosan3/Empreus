<?php

namespace App\Livewire\Task;

use App\Models\StatusType;
use Flux\Flux;
use Livewire\Component;
use Flux\DateRange;
use Carbon\Carbon;
use App\Models\TaskType;
use App\Models\Team;
use App\Models\Task;
use App\Models\Lot;






class TaskScheduler extends Component
{
    public $activeTab="calendar";
    public DateRange $range;
    public $currentRangeStart;
    public int $rangeDays = 7;

    public array $tasksByDay = [];
    public array $lots = [];
    public array $tasks = [];
    public array $subdivisions = [];

    public array $statuses = [];
    public array $hasMore = [];

    public array $boardTasks   = [];
    public array $boardStatuses = [];
    private int $pageSize = 15;



    protected $listeners = [
        'refreshView'       => 'loadData',
        'taskDateUpdated'   => 'taskDateUpdated',
        'taskStatusUpdated' => 'taskStatusUpdated',
        'load-more'         =>  'loadMore',
    ];

    public function statuses(): array
    {
        return [
            \App\Enums\StatusType::CONFIRMED->value      => 'Confirmed',
            \App\Enums\StatusType::IN_PROGRESS->value    => 'In Progress',
            \App\Enums\StatusType::PAYMENT_PENDING->value => 'Payment Pending',
            \App\Enums\StatusType::COMPLETED->value      => 'Completed',
            \App\Enums\StatusType::BLOCKED->value        => 'Blocked',
        ];
    }

    public function mount()
    {
        $this->range = DateRange::thisWeek();
        $this->currentRangeStart = $this->range->copy()->start();



        //Preload filter options for the navigation
        $columns = [
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

        $filters = [
            'task_type' => TaskType::orderBy('name')->get()->map(fn($t) => ['value' => $t->id, 'label' => $t->name])->toArray(),
            'team'      => Team::orderBy('name')->get()->map(fn($t) => ['value' => $t->id, 'label' => $t->name])->toArray(),
        ];

        $this->resetChildData();
        $this->loadData();

        $this->dispatch('update-columns', columns: $columns);
        $this->dispatch('filters-updated', filters: $filters);
    }

    private function resetChildData(): void
   {
       $this->tasks        = [];
       $this->tasksByDay   = [];
       $this->lots         = [];
       $this->subdivisions = [];
   }

    protected function slideRange(int $direction): void
    {
        $this->currentRangeStart->addDays($direction * $this->rangeDays);

        $start = $this->currentRangeStart->copy();
        $end   = $start->copy()->addDays($this->rangeDays - 1);

        $this->range = new DateRange($start, $end);
    }

    public function loadTasks(): void
    {
        logger()->debug('TaskScheduler::loadTasks called', func_get_args());

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

    public function previousRange()
    {
        $this->slideRange(-1);
        $this->loadData();
    }

    public function taskDateUpdated($taskId, $newDate)
    {

        try {
            $task = Task::find($taskId);
            if (!$task) {
                Flux::toast('Task Not Found');
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
            $this->loadData();

            Flux::toast(text:'Task re-scheduled successfully', variant: 'success');

        } catch (\Exception $e) {
            \Log::error('Failed to update task date', [
                'task_id' => $taskId,
                'new_date' => $newDate,
                'error' => $e->getMessage()
            ]);
            Flux::toast(text:'Failed to re-schedule Task day.', variant: 'danger');
        }
    }

    public function taskStatusUpdated(int $taskId, string $newStatus): void
    {
        try {
            // 1. Resolve the requested status
            $statusTypeId = StatusType::where('identifier', $newStatus)
                ->value('id');

            if (!$statusTypeId) {
                $this->showError('Invalid status.');
                Flux::toast('Invalid status.');
                return;
            }

            // 2. Fetch the task (we no longer keep a local cache)
            $task = Task::find($taskId);

            if (!$task) {
                Flux::toast('Task not found.');
                return;
            }

            // 3. Nothing to do if the task is already in that column
            if ($task->status_type_id === $statusTypeId) {
                return;
            }

            // 4. Persist quietly (avoid timestamps/events if you wish)
            $task->updateQuietly(['status_type_id' => $statusTypeId]);

            // 5. Ask the parent component to refresh its data
            $this->loadData();
            Flux::toast(text:'Task moved successfully.', variant: 'success');

        } catch (\Throwable $e) {
            \Log::error('Failed to update task status', [
                'task_id'    => $taskId,
                'new_status' => $newStatus,
                'error'      => $e->getMessage(),
            ]);
            Flux::toast(text:'Failed to update task status.', variant: 'danger');
        }
    }

    public function nextRange()
    {
        $this->slideRange(1);
        $this->loadData();
        $this->dispatch('rangeChanged');
    }

    public function today()
    {
        $this->currentRangeStart = Carbon::now()->startOfWeek();
        $this->rangeDays = 7;

        $this->range = DateRange::thisWeek();
        $this->loadData();
        $this->dispatch('rangeChanged');
    }

    // this is only updated when you actually handle the datepicker.
    public function updatedRange(DateRange $new)
    {
        $this->currentRangeStart = $this->range->copy()->start();
        $this->rangeDays = count($this->range->days()->toArray());
        $this->dispatch('rangeChanged');
    }

    /**
     * Fetch every piece of information the three child components
     * (TaskBoard, TaskCalendar, LotMap) need, using ONE query.
     */
    public function loadData(): void
    {
        $start = $this->range->copy()->start();
        $end   = $this->range->copy()->end();

        /*
         |------------------------------------------------------------------
         | 1.  Tasks + relationships
         |------------------------------------------------------------------
         | Relationships included:
         |   • taskType        – calendar & board show the task name / colour
         |   • statusType      – board groups by status identifier
         |   • team            – shown on cards (optional, keep if displayed)
         |   • lot             – address / subdivision
         |   • lot.builder     – builder’s name on cards & tooltip
         |
         | Restrict the selected columns to the ones that the views touch;
         | that keeps the payload small without breaking lazy-loaded props.
         */
        $tasks = Task::query()
            ->with([
                'taskType:id,name',     // adjust column list as required
                'statusType:id,identifier,name,color,color_dark',
                'lot:id,address,subdivision,builder_id',
                'lot.builder:id,name',
            ])
            ->whereBetween('scheduled_start_date', [$start, $end])
            ->orderBy('scheduled_start_date')
            ->get();

        /*
         |------------------------------------------------------------------
         | 2.  Data for the task-board (grouped by status)
         |------------------------------------------------------------------
         | We keep the inner values as collections of MODELS so the board
         | may still call `$task->id`, `$task->lot->address`, etc.
         */
        $this->tasks = $tasks
            ->groupBy(fn ($t) => $t->statusType?->identifier ?? 'unknown')
            ->map(fn ($group) => $group->take(15))
            ->all();

        /*
         |------------------------------------------------------------------
         | 3.  Data for the calendar (grouped by day)
         |------------------------------------------------------------------
         */
        $this->tasksByDay = $tasks
            ->filter(fn ($t) => filled($t->scheduled_start_date))
            ->groupBy(function ($t) {
                // guard against “string instead of Carbon” situations
                return $t->scheduled_start_date instanceof \Carbon\CarbonInterface
                    ? $t->scheduled_start_date->toDateString()
                    : \Carbon\Carbon::parse($t->scheduled_start_date)->toDateString();
            })
            ->map(fn ($group) => $group->values())
            ->all();


        /*
         |------------------------------------------------------------------
         | 4.  Lots for the map
         |------------------------------------------------------------------
         */
        $lots = Lot::confirmedTasksInRange($this->range)
            ->get(['id', 'address', 'subdivision']);

        $this->lots         = $lots->toArray();
        $this->subdivisions = $lots
            ->pluck('subdivision')
            ->unique()
            ->values()
            ->toArray();


        $this->statuses = $this->statuses();

        foreach ($this->statuses as $status => $label) {
            $count = ($this->tasks[$status] ?? collect())->count();
            $this->hasMore[$status] = $count >= 15;
        }
    }

    public function loadMore(string $status): void
    {
        $start   = $this->range->copy()->start();
        $end     = $this->range->copy()->end();
        $current = collect($this->tasks[$status] ?? []);

        $more = Task::getTasksByStatusForCurrentCompany(
            $status,
            15,
            $current->count(),
            $start,
            $end
        );

        $this->tasks[$status] = $current->concat($more)->all();
        $this->hasMore[$status] = $more->count() === 15;
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

    #[Computed]
    public function isCurrentRange(): bool
    {
        return $this->currentRangeStart->copy()->eq(Carbon::now()->startOfWeek());
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.task.task-scheduler')->layout('layouts.app', [
            'showFilters' => true,
            'showSearch'  => false,
            'showColumns' => false,
            'showAddNew'  => false,
            'currentAddable' => 'task',
        ]);
    }
}
