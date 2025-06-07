<?php

namespace App\Livewire\Task;

use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Cache;

use Flux\Flux;

class TaskProgress extends Component
{
    use WithPagination;

    public $sortBy = 'id';
    public $sortDirection = 'desc';

    public $columns = [];
    public $selectedBuilders = [];
    public $selectedSubdivisions = [];
    public $selectedLots = [];

    public $searchTerm = '';

    public array $checkedTasks = [];

    // For dynamically displaying task details
    public $selectedTask = null;

    protected $listeners = [
        'contentUpdated' => '$refresh',
        'search-tasks' => 'updateSearch',
    ];

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
        $perPage = 20;
        $page    = request()->get('page', 1);

        $query = Auth::user()->tasksForCurrentCompany()
        ->with([
            'lot.builder',            // lot + nested builder
            'lot.supervisor',         // lot + nested supervisor
            'taskType',               // one-to-many
            'statusType',             // one-to-many
            // load only the latest note
            'taskNotes' => fn($q) => $q->latest('created_at')->limit(1),
        ]);

        if ($this->searchTerm !== '') {
            $ids = Task::search($this->searchTerm)->keys()->toArray();
            $query->whereIn('tasks.id', $ids);

            // Keep Meilisearch ranking if there are results
            if (!empty($ids)) {
                $query->orderByRaw('FIELD(tasks.id, ' . implode(',', $ids) . ')');
            }
        }

        // Start with tasks for the current company
        //$query = Auth::user()->tasksForCurrentCompany();

        // 1. Filtering by builder
        if (!empty($this->selectedBuilders)) {
            $query->whereHas('lot.builder', function ($q) {
                $q->whereIn('name', $this->selectedBuilders);
            });
        }

        // 2. Filtering by subdivision/phase
        if (!empty($this->selectedSubdivisions)) {
            $query->whereHas('lot', function ($q) {
                $q->where(function ($q2) {
                    foreach ($this->selectedSubdivisions as $filter) {
                        [$subdivision, $phase] = explode('|', $filter);
                        $q2->orWhere(function ($q3) use ($subdivision, $phase) {
                            $q3->where('subdivision', $subdivision)
                            ->where('phase', $phase);
                        });
                    }
                });
            });
        }

        // 3. Filtering by lot number
        if (!empty($this->selectedLots)) {
            $query->whereHas('lot', function ($q) {
                $q->whereIn('lots.lot_number', $this->selectedLots);
            });
        }

        // 4. Sorting logic
        //    We join the lots (and builders if needed) to sort by columns that live on those tables.
        if ($this->sortBy === 'builder') {
            // Sorting by builder (which is on the lots->builders relationship)
            $query->join('lots', 'tasks.lot_id', '=', 'lots.id')
                  ->join('builders', 'lots.builder_id', '=', 'builders.id')
                  ->orderBy('builders.name', $this->sortDirection);
        } elseif ($this->sortBy === 'subdivision') {
            $query->join('lots', 'tasks.lot_id', '=', 'lots.id')
                  ->orderBy('lots.subdivision', $this->sortDirection);
        } elseif ($this->sortBy === 'address') {
            $query->join('lots', 'tasks.lot_id', '=', 'lots.id')
                  ->orderBy('lots.address', $this->sortDirection);
        } elseif ($this->sortBy === 'lot_number') {
            // If lot_number is mostly numeric but sometimes text, you might use a raw order:
            $query->join('lots', 'tasks.lot_id', '=', 'lots.id')
                  ->orderByRaw("CAST(lots.lot_number AS UNSIGNED) {$this->sortDirection}, lots.lot_number ASC");
        } elseif ($this->sortBy === 'num_of_floors') {
            $query->join('lots', 'tasks.lot_id', '=', 'lots.id')
                  ->orderBy('lots.num_of_floors', $this->sortDirection);
        } elseif ($this->sortBy === 'floor_area') {
            $query->join('lots', 'tasks.lot_id', '=', 'lots.id')
                  ->orderBy('lots.floor_area', $this->sortDirection);
        } elseif ($this->sortBy === 'supervisor') {
            // Sorting by supervisor name, which we assume comes from the lots->supervisor relationship
            $query->join('lots', 'tasks.lot_id', '=', 'lots.id')
                  ->join('supervisors', 'lots.supervisor_id', '=', 'supervisors.id')
                  ->orderBy('supervisors.name', $this->sortDirection);
        } elseif ($this->sortBy === 'task_type') {
            // Sorting by task type (from task_types table)
            $query->join('task_types', 'tasks.task_type_id', '=', 'task_types.id')
                  ->orderBy('task_types.name', $this->sortDirection);
        } elseif ($this->sortBy === 'status') {
            // Sorting by status (from status_types table)
            $query->join('status_types', 'tasks.status_type_id', '=', 'status_types.id')
                  ->orderBy('status_types.name', $this->sortDirection);
        } elseif ($this->sortBy === 'notes') {
            // Sorting by note can be trickier (if tasks can have many notes). For a simple case,
            // if you want to sort by the description of the first note, you might do a left join.
            $query->leftJoin('task_notes', 'tasks.id', '=', 'task_notes.task_id')
                  ->orderBy('task_notes.description', $this->sortDirection);
        } elseif ($this->sortBy === 'is_extra') {
            // This column is directly on the tasks table
            $query->orderBy('tasks.is_extra', $this->sortDirection);
        } else {
            // Default: assume the column is on the tasks table
            $query->orderBy('tasks.' . $this->sortBy, $this->sortDirection);
        }
        
        // Finally, paginate the result
        return $query->paginate(20);
    }

    #[Computed]
    public function getFlatTasksProperty()
    {
        // We’ll build a simple array-of-arrays, one per task
        return $this->tasks->map(function($task) {
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

                switch ($column['value']) {
                    case 'builder':
                        $display        = $task->lot->builder->name;
                        $rawValue       = $task->lot->builder_id;
                        $modelClass     = \App\Models\Lot::class;
                        $modelAttribute = 'builder_id';
                        break;
                    case 'subdivision':
                        $display        = $task->lot->subdivision;
                        $rawValue       = $display;
                        $modelClass     = \App\Models\Lot::class;
                        $modelAttribute = 'subdivision';
                        break;
                    case 'address':
                        $display        = $task->lot->address ?? '-';
                        $rawValue       = $display;
                        $modelClass     = \App\Models\Lot::class;
                        $modelAttribute = 'address';
                        break;
                    case 'lot_number':
                        $display        = $task->lot->lot_number;
                        $rawValue       = $display;
                        $modelClass     = \App\Models\Lot::class;
                        $modelAttribute = 'lot_number';
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
                        break;
                    case 'floor_area':
                        $display        = $task->lot->floor_area ?? '-';
                        $rawValue       = $display;
                        $modelClass     = \App\Models\Lot::class;
                        $modelAttribute = 'floor_area';
                        break;
                    case 'supervisor':
                        $display        = $task->lot->supervisor->name ?? '-';
                        $rawValue       = $task->lot->supervisor_id;
                        $modelClass     = \App\Models\Lot::class;
                        $modelAttribute = 'supervisor_id';
                        break;
                    case 'is_extra':
                        $display        = $task->is_extra ? 'EPO' : 'Standard';
                        $rawValue       = $task->is_extra;
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
                $map = [
                    'builder'    => 'builders',
                    'task_type'  => 'taskTypes',
                    'status'     => 'statusTypes',
                    'supervisor' => 'supervisors',
                    'subdivision'=> 'subdivisions',
                    'is_extra'   => 'is_extra',
                ];
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

    /**
     * Livewire 3 event listener for the Navigation's "global-filters-updated" event.
     * Updates our local filter arrays, then re-dispatches to recalc the dropdowns.
     */
    #[On('global-filters-updated')]
    public function handleGlobalFilters(array $data)
    {
        $this->selectedBuilders    = $data['builders']      ?? [];
        $this->selectedSubdivisions = $data['subdivisions'] ?? [];
        $this->selectedLots       = $data['lots']          ?? [];

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
    public function dispatchFilters()
    {
        $companyId = Auth::id();
        $ttl       = 300; // cache TTL in seconds

        // Shorthand for your three filter arrays
        $bFilters = $this->selectedBuilders;
        $sFilters = $this->selectedSubdivisions;
        $lFilters = $this->selectedLots;

        //
        // 1) BUILDERS LIST — cache keyed by [subdivisions, lots]
        //
        $builders = Cache::remember(
            "taskProgress:{$companyId}:builders:" . md5(json_encode([$sFilters, $lFilters])),
            $ttl,
            function () use ($sFilters, $lFilters) {
                $q = Auth::user()->tasksForCurrentCompany();

                // apply only subdivisions & lots filters
                if (!empty($sFilters)) {
                    $q->whereHas('lot', fn($q) =>
                        $q->where(function ($q2) use ($sFilters) {
                            foreach ($sFilters as $filter) {
                                [$sub, $ph] = explode('|', $filter);
                                $q2->orWhere(fn($q3) =>
                                    $q3->where('subdivision', $sub)
                                    ->where('phase',       $ph)
                                );
                            }
                        })
                    );
                }
                if (!empty($lFilters)) {
                    $q->whereHas('lot', fn($q) =>
                        $q->whereIn('lot_number', $lFilters)
                    );
                }

                return $q->join('lots',     'tasks.lot_id',       '=', 'lots.id')
                        ->join('builders', 'lots.builder_id',    '=', 'builders.id')
                        ->distinct()
                        ->orderBy('builders.name', 'asc')
                        ->pluck('builders.name')
                        ->toArray();
            }
        );

        //
        // 2) SUBDIVISIONS LIST — cache keyed by [builders, lots]
        //
        $subdivisions = Cache::remember(
            "taskProgress:{$companyId}:subdivisions:" . md5(json_encode([$bFilters, $lFilters])),
            $ttl,
            function () use ($bFilters, $lFilters) {
                $q = Auth::user()->tasksForCurrentCompany();

                // apply only builders & lots filters
                if (!empty($bFilters)) {
                    $q->whereHas('lot.builder', fn($q) =>
                        $q->whereIn('name', $bFilters)
                    );
                }
                if (!empty($lFilters)) {
                    $q->whereHas('lot', fn($q) =>
                        $q->whereIn('lot_number', $lFilters)
                    );
                }

                return $q->join('lots', 'tasks.lot_id', '=', 'lots.id')
                        ->select('lots.subdivision','lots.phase')
                        ->groupBy('lots.subdivision','lots.phase')
                        ->orderBy('lots.subdivision','asc')
                        ->get()
                        ->map(fn($item) => [
                            'value' => "{$item->subdivision}|{$item->phase}",
                            'label' => $item->subdivision.($item->phase ? " ({$item->phase})" : ''),
                        ])
                        ->toArray();
            }
        );

        //
        // 3) LOT NUMBERS — cache keyed by [builders, subdivisions]
        //
        $lots = Cache::remember(
            "taskProgress:{$companyId}:lots:" . md5(json_encode([$bFilters, $sFilters])),
            $ttl,
            function () use ($bFilters, $sFilters) {
                $q = Auth::user()->tasksForCurrentCompany();

                // apply only builders & subdivisions filters
                if (!empty($bFilters)) {
                    $q->whereHas('lot.builder', fn($q) =>
                        $q->whereIn('name', $bFilters)
                    );
                }
                if (!empty($sFilters)) {
                    $q->whereHas('lot', fn($q) =>
                        $q->where(function ($q2) use ($sFilters) {
                            foreach ($sFilters as $filter) {
                                [$sub, $ph] = explode('|', $filter);
                                $q2->orWhere(fn($q3) =>
                                    $q3->where('subdivision', $sub)
                                    ->where('phase',       $ph)
                                );
                            }
                        })
                    );
                }

                return $q->join('lots', 'tasks.lot_id', '=', 'lots.id')
                        ->distinct()
                        ->orderBy('lots.lot_number','asc')
                        ->pluck('lots.lot_number')
                        ->toArray();
            }
        );

        // Finally, dispatch exactly as before:
        $this->dispatch('filters-updated',
            builders:    $builders,
            subdivisions:$subdivisions,
            lots:        $lots,
        );

        $this->resetPage();
    }


    /**
     * Load task details dynamically or redirect if using route-based navigation.
     */
    public function loadDetails($taskId)
    {
        if (!$this->isRouteRequest()) {
            $this->selectedTask = Task::findOrFail($taskId);
        } else {
            return redirect()->route('task-details', ['task_id' => $taskId]);
        }
    }

    /**
     * Check if the current request is route-based.
     */
    private function isRouteRequest()
    {
        if (request()->header('X-Livewire')) {
            return false;
        }
        return request()->routeIs('task-details') || request()->routeIs('task-progress');
    }

    /**
     * Reset the selected task to return to the table view.
     */
    #[On('back-button-press')]
    public function resetSelectedTask($selectedTask = null)
    {
        $this->selectedTask = $selectedTask;
    }

    #[On('column-list-updated')]
    public function updateColumns($columns)
    {
        $this->columns = $columns;
    }

    /**
     * On mount, initialize the columns and dispatch initial filter data.
     */
    public function mount()
    {
        $this->columns = [
            ['id' => 1,  'label' => 'Builder',         'value' => 'builder',         'group' => 'fixed',  'selected' => true,  'sortable' => true, 'editable' => false],
            ['id' => 2,  'label' => 'Subdivision',     'value' => 'subdivision',     'group' => 'fixed',  'selected' => true,  'sortable' => true, 'editable' => false],
            ['id' => 3,  'label' => 'Lot',             'value' => 'lot_number',             'group' => 'fixed',  'selected' => true,  'sortable' => true, 'editable' => false],
            ['id' => 4,  'label' => 'Task Type',       'value' => 'task_type',       'group' => 'active', 'selected' => true,  'sortable' => true, 'editable' => false],
            ['id' => 5,  'label' => 'Status',          'value' => 'status',          'group' => 'active', 'selected' => true,  'sortable' => true, 'editable' => true],
            ['id' => 6,  'label' => 'Stories',         'value' => 'num_of_floors',         'group' => 'active', 'selected' => true,  'sortable' => true, 'editable' => true],
            ['id' => 7,  'label' => 'Notes',           'value' => 'notes',           'group' => 'active', 'selected' => true,  'sortable' => false, 'editable' => true],
            ['id' => 8,  'label' => 'SQ FT',         'value' => 'floor_area',    'group' => 'active', 'selected' => true,  'sortable' => true, 'editable' => true],
            ['id' => 9,  'label' => 'Supervisor',      'value' => 'supervisor',      'group' => 'active', 'selected' => true,  'sortable' => true, 'editable' => true],
            ['id' => 10, 'label' => 'Purchase Order',  'value' => 'order_number',  'group' => 'active', 'selected' => true,  'sortable' => true, 'editable' => true],
            ['id' => 11, 'label' => 'Planned Start',   'value' => 'scheduled_start_date',   'group' => 'active', 'selected' => true,  'sortable' => true, 'editable' => true],
            ['id' => 12, 'label' => 'Address',         'value' => 'address',         'group' => 'active', 'selected' => true,  'sortable' => true, 'editable' => true],
            ['id' => 13, 'label' => 'Price',           'value' => 'price',           'group' => 'active', 'selected' => true,  'sortable' => true, 'editable' => true],
            ['id' => 14, 'label' => 'Completion Date', 'value' => 'ended_at', 'group' => 'active', 'selected' => true,  'sortable' => true, 'editable' => true],
            ['id' => 15, 'label' => 'Order Type', 'value' => 'is_extra', 'group' => 'active', 'selected' => true,  'sortable' => true, 'editable' => true],
        ];

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
