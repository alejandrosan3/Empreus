<?php

namespace App\Livewire\Task;

use App\Models\Task;
use App\Models\StatusType;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Cache;
use App\Repositories\TaskProgressRepository;
use App\Traits\WithToast;
use App\Services\ColumnPreferenceService;

use Flux\Flux;

class TaskProgress extends Component
{
    use WithPagination, WithToast;

    public $sortBy;
    public $sortDirection;
    public $columns;

    // Selected filters keyed by column name
    public array $selectedFilters = [];

    public $searchTerm = '';

    public $checkedTasks = [];

    protected $listeners = [
        'contentUpdated' => '$refresh',
        'search-tasks' => 'updateSearch',
    ];


    protected TaskProgressRepository $repository;
    protected ColumnPreferenceService $columnPreferences;

    public function boot(TaskProgressRepository $repo, ColumnPreferenceService $columnPreferenceService)
    {
        $this->repository = $repo;
        $this->columnPreferences = $columnPreferenceService;
    }

    /**
     * Sort the table by a given column, toggling sort direction if needed,
     * then re-dispatch filter data.
     */
    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        $this->dispatchFilters();
    }

    public function updateSearch($query)
    {
        $this->searchTerm = $query;
        $this->resetPage();
    }

    /**
     * Computed property for retrieving tasks based on selected filters and sort.
     */
    public function getTasksProperty()
    {
        return $this->repository->getTasks(
            $this->selectedFilters,
            $this->sortBy,
            $this->sortDirection,
            $this->searchTerm
        );
    }

    #[Computed]
    public function getFlatTasksProperty()
    {
        $map = config('task-progress.option_map');

        // We’ll build a simple array-of-arrays, one per task
        return $this->tasks->map(function($task) use ($map) {
            // Start each row with its id
            $row = ['id' => $task->id];

            // Loop over the $columns config to keep order/visibility
            foreach ($this->columns as $column) {
                if (! $column['selected']) {
                    continue;
                }

                // Prepare defaults
                $display        = '-';
                $rawValue       = null;
                $fieldType      = 'input';
                $modelClass     = \App\Models\Task::class;
                $modelAttribute = $column['value'];
                $optionKey      = '';
                $editable       = $column['editable'];
                $recordId       = $task->id;

                switch ($column['value']) {
                    case 'builder':
                        $display        = $task->lot->builder->name;
                        $rawValue       = $task->lot->builder_id;
                        $modelClass     = \App\Models\Lot::class;
                        $modelAttribute = 'builder_id';
                        $recordId       = $task->lot->id;
                        break;
                    case 'subdivision':
                        $display        = $task->lot->subdivision . ($task->lot->phase ? " ({$task->lot->phase})" : '');
                        $rawValue       = $task->lot->subdivision;
                        $modelClass     = \App\Models\Lot::class;
                        $modelAttribute = 'subdivision';
                        $recordId       = $task->lot->id;
                        break;
                    case 'address':
                        $display        = $task->lot->address ?? '-';
                        $rawValue       = $display;
                        $modelClass     = \App\Models\Lot::class;
                        $modelAttribute = 'address';
                        $recordId       = $task->lot->id;
                        break;
                    case 'lot_number':
                        $display        = $task->lot->lot_number;
                        $rawValue       = $display;
                        $modelClass     = \App\Models\Lot::class;
                        $modelAttribute = 'lot_number';
                        $recordId       = $task->lot->id;
                        break;
                    case 'task_type':
                        $display        = $task->taskType->name;
                        $rawValue       = $task->taskType->id;
                        $modelAttribute = 'task_type_id';
                        break;
                    case 'status':
                        $display        = $task->statusType->name;
                        $rawValue       = $task->statusType->id;
                        $fieldType      = 'select';
                        $modelAttribute = 'status_type_id';
                        break;
                    case 'num_of_floors':
                        $display        = $task->lot->num_of_floors;
                        $rawValue       = $display;
                        $modelClass     = \App\Models\Lot::class;
                        $modelAttribute = 'num_of_floors';
                        $recordId       = $task->lot->id;
                        break;
                    case 'floor_area':
                        $display        = $task->lot->floor_area ?? '-';
                        $rawValue       = $display;
                        $modelClass     = \App\Models\Lot::class;
                        $modelAttribute = 'floor_area';
                        $recordId       = $task->lot->id;
                        break;
                    case 'supervisor':
                        $display        = $task->lot->supervisor->name ?? '-';
                        $rawValue       = $task->lot->supervisor_id;
                        $modelClass     = \App\Models\Lot::class;
                        $modelAttribute = 'supervisor_id';
                        $fieldType      = 'select';
                        $recordId       = $task->lot->id;
                        break;
                    case 'is_extra':
                        $display        = $task->is_extra ? 'EPO' : 'Standard';
                        $rawValue       = $task->is_extra;
                        $fieldType      = 'select';
                        break;
                    case 'notes':
                        $latestNote     = $task->taskNotes->first();
                        $display        = $latestNote->description ?? '-';
                        $rawValue       = $display;
                        $modelClass     = \App\Models\TaskNote::class;
                        $modelAttribute = 'description';
                        break;
                    case 'scheduled_start_date':
                        $display        = $task->scheduled_start_date;
                        $rawValue       = $display;
                        $fieldType      = 'date';
                        $modelAttribute = 'scheduled_start_date';
                        break;
                    case 'scheduled_end_date':
                        $display        = $task->scheduled_end_date;
                        $rawValue       = $display;
                        $fieldType      = 'date';
                        $modelAttribute = 'scheduled_end_date';
                        break;
                    case 'started_at':
                        $display        = $task->started_at;
                        $rawValue       = $display;
                        $fieldType      = 'date';
                        $modelAttribute = 'started_at';
                        break;
                    case 'phase':
                        $display        = $task->lot->phase;
                        $rawValue       = $display;
                        $modelClass     = \App\Models\Lot::class;
                        $modelAttribute = 'phase';
                        $recordId       = $task->lot->id;
                        break;
                    case 'ended_at':
                        $display        = $task->ended_at;
                        $rawValue       = $display;
                        $fieldType      = 'date';
                        $modelAttribute = 'ended_at';
                        break;
                    default:
                        if (isset($task->{$column['value']})) {
                            $display        = $task->{$column['value']};
                            $rawValue       = $display;
                        }
                }

                // Map column keys for option lists if needed
                $optionKey = $map[$column['value']] ?? '';

                // Put it all into the row under the column’s `value` key
                $row[$column['value']] = [
                    'display'        => $display,
                    'rawValue'       => $rawValue,
                    'fieldType'      => $fieldType,
                    'modelClass'     => $modelClass,
                    'modelAttribute' => $modelAttribute,
                    'optionKey'      => $optionKey,
                    'editable'       => $editable,
                    'recordId'       => $recordId,
                    // include color for badges:
                    'color'          => $column['value'] === 'status'
                        ? $task->statusType->color
                        : null,
                ];
            }

            return $row;
        })->toArray();
    }

    public function startBulkEdit()
    {
        $this->dispatch('bulk-edit-action', entity: 'task', ids: $this->checkedTasks );
    }

    public function startBulkAssign()
    {
        if (! empty($this->checkedTasks)) {
            $this->dispatch('open-bulk-assign-modal', ids: $this->checkedTasks);
        }
    }

    /**
     * Livewire 3 event listener for the Navigation's "global-filters-updated" event.
     * Updates our local filter arrays, then re-dispatches to recalc the dropdowns.
     */
    #[On('global-filters-updated')]
    public function handleGlobalFilters(array $filters)
    {
        $this->selectedFilters = $filters;

        $this->resetPage();
        $this->dispatchFilters();
    }

    /**
     * Re-dispatch filter data when sorting changes.
     */
    public function updated($propertyName)
    {
        if (in_array($propertyName, ['sortBy', 'sortDirection'])) {
            $this->dispatchFilters();
        }
    }

    /**
     * Dispatch filter options for the toolbar dropdowns.
     */
    #[On('company-updated')]
    public function clearCacheAndDispatch()
    {
        Cache::flush();
        $this->dispatchFilters();
    }

    public function dispatchFilters()
    {
        $filters = $this->repository->filterOptions($this->selectedFilters, $this->columns);

        $this->dispatch('filters-updated', filters: $filters);
        $this->resetPage();
    }


    #[On('column-list-updated')]
    public function updateColumns($columns)
    {
        $this->columns = $columns;
        $this->columnPreferences->setColumns(auth()->user(),'task-progress', $columns);
        $this->dispatchFilters();
    }


    /**
     * Create a copy of the current task.
     *
     */
    public function duplicateTask(int $taskId): void
    {
        try {
            $original = Task::findOrFail($taskId);
            // Look up the “Pending” status ID
            $pendingStatusId = StatusType::where('name', 'pending')->value('id');
            $copy     = $original->duplicate(['status_type_id' => $pendingStatusId]);


            $this->showSuccess(
                "Task #{$original->taskType->name} was duplicated successfully"
            );

            $this->dispatch('contentUpdated');
        } catch (\Throwable $e) {
            $this->showError("Could not duplicate Task: {$e->getMessage()}");
        }
    }

    /**
     * On mount, initialize the columns and dispatch initial filter data.
     */
    public function mount(TaskProgressRepository $repository)
    {
        // If no builder, jump to create view
        if (Task::count() === 0) {
             $this->redirectRoute('task-create');
        }

        $this->repository = $repository;

        // load everything from config/task-progress.php
        $this->columns       = $this->columnPreferences->getColumns(auth()->user(), 'task-progress');
        $this->sortBy        = config('task-progress.default_sort_by');
        $this->sortDirection = config('task-progress.default_sort_direction');


        $this->dispatchFilters();
        $this->dispatch('update-columns', columns: $this->columns);
    }

    public function render()
    {
        return view('livewire.task.task-progress', [
            'tasks' => $this->tasks,
            'flatTasks' => $this->flatTasks,
        ])->layout('layouts.app', [
            'showFilters' => true,
            'showSearch'  => true,
            'showColumns' => true,
            'showAddNew'  => true,
            'currentAddable'  => 'task',
        ]);
    }
}
