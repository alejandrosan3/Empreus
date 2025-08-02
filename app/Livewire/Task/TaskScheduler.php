<?php

namespace App\Livewire\Task;

use AllowDynamicProperties;
use App\Models\StatusType;
use Flux\Flux;
use Livewire\Component;
use Flux\DateRange;
use Carbon\Carbon;
use App\Models\TaskType;
use App\Models\Task;
use App\Models\Lot;


#[AllowDynamicProperties] class TaskScheduler extends Component
{
    public DateRange $range;
    public $activeTab="calendar";
    public array $weekDays = [];
    public int       $rangeDays      = 7;
    public int       $pageSize       = 15;
    public array $tasksByDay = [];
    public array $lots = [];
    public array $tasks = [];
    public array $subdivisions = [];
    public $currentRangeStart;

    public array $statuses = [];
    public array $hasMore = [];

    public array $howManyMore = [];

    public int $chunk = 15;
    // this will help refresh the children component when changes are made.
    public int $refreshKey;
    public array $selectedFilters = [
        'task_type' => [],
    ];

    protected $listeners = [
        'refreshView'     => 'loadData',
        'taskDateUpdated' => 'taskDateUpdated',
        'taskStatusUpdated' => 'taskStatusUpdated',
        'load-more'       => 'loadMore',
        'global-filters-updated' => 'filtersUpdated',
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


    public function mount(): void
    {
        // initialise the default date-range once
        $this->range ??= DateRange::thisWeek();

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
        ];

        $filters = [
            'task_type' => TaskType::orderBy('name')->get()->map(fn($t) => ['value' => $t->id, 'label' => $t->name])->toArray(),
        ];

        $this->resetChildData();
        $this->loadData();

        $this->dispatch('update-columns', columns: $columns);
        $this->dispatch('filters-updated', filters: $filters);
    }

    public function updateTimeStamp(): void
    {
        $this->refreshKey = now()->timestamp;
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
        $taskTypeIds = $this->filterIds($this->selectedFilters['task_type'] ?? []);


        $tasks = Task::query()
            ->select(
                'tasks.*',
                'lots.subdivision as subdivision',
                'lots.lot_number  as lot_number',
                'lots.address     as address',
                'task_types.name  as task_type_name',
                'supervisors.name as supervisor_name',
                'builders.name    as builder_name',
            )
            ->join('task_types', 'tasks.task_type_id', '=', 'task_types.id')
            ->join('lots',       'tasks.lot_id',       '=', 'lots.id')
            ->join('supervisors','lots.supervisor_id', '=', 'supervisors.id')
            ->join('status_types','tasks.status_type_id','=','status_types.id')
            ->join('builders',   'lots.builder_id',    '=', 'builders.id')
            ->where('tasks.company_id', session('company_id'))
            ->whereIn('status_types.identifier', ['confirmed','in_progress'])
            ->whereBetween('tasks.scheduled_start_date', [$startDate, $endDate])

            ->when($taskTypeIds, fn ($q) =>
            $q->whereIn('tasks.task_type_id', $taskTypeIds)
            )

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


    public function filtersUpdated(array $filters): void
    {
        $this->selectedFilters = $filters;
        $this->loadData();
        $this->updateTimeStamp();

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
        $this->updateTimeStamp();
    }

    public function today()
    {
        $this->currentRangeStart = Carbon::now()->startOfWeek();
        $this->rangeDays = 7;

        $this->range = DateRange::thisWeek();
        $this->loadData();
        $this->dispatch('rangeChanged');
        $this->updateTimeStamp();
    }

    // this is only updated when you actually handle the datepicker.
    public function updatedRange(DateRange $new)
    {
        $this->currentRangeStart = $this->range->copy()->start();
        $this->rangeDays = count($this->range->days()->toArray());
        $this->updateTimeStamp();
        $this->dispatch('rangeChanged');
    }

    private function filterIds(array $raw): array
    {
        return collect($raw)
            ->map(fn ($item) =>          // accept either “3” or ['value'=>3,'label'=>…]
            is_array($item) ? ($item['value'] ?? null) : $item
            )
            ->filter()                   // drop nulls
            ->values()
            ->all();                     // ➜ [1,3,5]
    }

    /**
     * Fetch every piece of information the three child components
     * (TaskBoard, TaskCalendar, LotMap) need, using ONE query.
     */
    public function loadData(): void
    {
        $start = $this->range->copy()->start();
        $end   = $this->range->copy()->end();
        $taskTypeIds = $this->filterIds($this->selectedFilters['task_type'] ?? []);

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
                'taskType:id,name',
                'statusType:id,identifier,name,color,color_dark',
                'lot:id,address,subdivision,builder_id',
                'lot.builder:id,name',
            ])
            ->whereBetween('scheduled_start_date', [$start, $end])
            ->when($taskTypeIds, fn ($q) =>
            $q->whereIn('task_type_id', $taskTypeIds)
            )
            ->orderBy('scheduled_start_date')
            ->get();


        $this->tasks = $tasks
            ->groupBy(fn ($t) => $t->statusType?->identifier ?? 'unknown')
            ->map(fn ($group) => $group->take($this->pageSize))
            ->all();


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
            $this->hasMore[$status] = $count > $this->pageSize;
            $this->howManyMore[$status] = $count - $this->pageSize;
        }

        foreach ($this->range->days() as $carbonDate) {
            $date = $carbonDate->toDateString();

            // full result set for counting/paging
            $allTasks = Task::whereDate('scheduled_start_date', $date)
                ->when($taskTypeIds, fn ($q) =>
                $q->whereIn('task_type_id', $taskTypeIds)
                )
                ->orderBy('scheduled_start_date')
                ->get();

            // 1️⃣ keep only the first page in the public property
            $this->tasksByDay[$date] = $allTasks->take($this->pageSize)->values()->all();

            // 2️⃣ tell the Blade whether there is more to load
            $this->hasMore[$date]   = $allTasks->count() > $this->pageSize;
            $this->howManyMore[$date] = $allTasks->count() - $this->pageSize;
        }
        $this->updateTimeStamp();
    }

    public function loadMore(string $status=null, $date=null): void
    {
        logger()->info('loadMoreTasks – ENTER', [
            'date'  => $date,
            'current' => count($this->tasksByDay[$date] ?? []),
        ]);

        $start   = $this->range->copy()->start();
        $end     = $this->range->copy()->end();
        $pageSize = $this->pageSize;
        $chunk = 15;

        if ($status) {
            $current = collect($this->tasks[$status] ?? []);

            $more = Task::getTasksByStatusForCurrentCompany(
                $status,
                $chunk,
                $current->count(),   // offset
                $start,
                $end
            );
            $this->tasks[$status]  = $current->concat((array)$more)->all();
            $this->hasMore[$status] = $more->count() === $chunk;
            $this->howManyMore[$status] = $this->howManyMore[$status] - $chunk;

            if ($more->count() === $chunk) {
                $this->pageSize += $chunk;
            }
        }

        if ($date) {
            $current = collect($this->tasksByDay[$date] ?? []);


            $more = Task::getTasksByDatesForCurrentCompany(
            // same day for both start & end → single day filter
                \Carbon\Carbon::parse($date),
                \Carbon\Carbon::parse($date),
                $chunk,
                $current->count()    // offset
            );

            $this->tasksByDay[$date] = $current->concat((array)$more)->all();
            $this->hasMore[$date]    = $more->count() === $chunk;
            $this->howManyMore[$date] = $this->howManyMore[$date] - $chunk;

            if ($more->count() === $chunk) {
                $this->pageSize += $chunk;
            }

            logger()->info('loadMoreTasks – LEAVE', [
                'Date'  => $date,
                'current' => count($this->tasksByDay[$date] ?? []),
                'hasMore' => $this->hasMore[$date] ?? null,
            ]);
        }
        $this->updateTimeStamp();
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
        $this->updateTimeStamp();
    }

    #[Computed]
    public function isCurrentRange(): bool
    {
        return $this->currentRangeStart->copy()->eq(Carbon::now()->startOfWeek());
        $this->updateTimeStamp();
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
