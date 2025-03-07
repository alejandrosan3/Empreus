<?php

namespace App\Livewire\Task;

use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

class TaskProgress extends Component
{
    use WithPagination;

    public $sortBy = 'id';
    public $sortDirection = 'desc';

    public $columns = [];
    public $selectedBuilders = [];
    public $selectedSubdivisions = [];
    public $selectedLots = [];

    // For dynamically displaying task details
    public $selectedTask = null;

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

    /**
     * Computed property for retrieving tasks based on selected filters and sort.
     */
    public function getTasksProperty()
    {
        // Start with tasks for the current company
        $query = Auth::user()->tasksForCurrentCompany();

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
        $query = Auth::user()->tasksForCurrentCompany();

        // Filter by builder through the lot relationship
        if (!empty($this->selectedBuilders)) {
            $query->whereHas('lot.builder', function ($q) {
                $q->whereIn('name', $this->selectedBuilders);
            });
        }

        // Filter by subdivision/phase through the lot relationship
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

        // Filter by lot number through the lot relationship
        if (!empty($this->selectedLots)) {
            $query->whereHas('lot', function ($q) {
                $q->whereIn('lot_number', $this->selectedLots);
            });
        }

        // Get builders from the joined tables: tasks -> lots -> builders
        $builders = $query->clone()
            ->join('lots', 'tasks.lot_id', '=', 'lots.id')
            ->join('builders', 'lots.builder_id', '=', 'builders.id')
            ->distinct()
            ->orderBy('builders.name', 'asc')
            ->pluck('builders.name');

        // Get subdivisions (and phases) from lots
        $subdivisions = $query->clone()
            ->join('lots', 'tasks.lot_id', '=', 'lots.id')
            ->select('lots.subdivision', 'lots.phase')
            ->groupBy('lots.subdivision', 'lots.phase')
            ->orderBy('lots.subdivision', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->subdivision . '|' . $item->phase,
                    'label' => $item->subdivision . ($item->phase ? " ({$item->phase})" : ""),
                ];
            })
            ->toArray();

        // Get lot numbers from the joined tasks and lots
        $lots = $query->clone()
            ->join('lots', 'tasks.lot_id', '=', 'lots.id')
            ->distinct()
            ->orderBy('lots.lot_number', 'asc')
            ->pluck('lots.lot_number');

        $this->dispatch('filters-updated',
            builders: $builders,
            subdivisions: $subdivisions,
            lots: $lots
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
            ['id' => 1,  'label' => 'Builder',         'value' => 'builder',         'group' => 'fixed',  'selected' => true,  'sortable' => true],
            ['id' => 2,  'label' => 'Subdivision',     'value' => 'subdivision',     'group' => 'fixed',  'selected' => true,  'sortable' => true],
            ['id' => 3,  'label' => 'Lot',             'value' => 'lot_number',             'group' => 'fixed',  'selected' => true,  'sortable' => true],
            ['id' => 4,  'label' => 'Task Type',       'value' => 'task_type',       'group' => 'active', 'selected' => true,  'sortable' => true],
            ['id' => 5,  'label' => 'Status',          'value' => 'status',          'group' => 'active', 'selected' => true,  'sortable' => true],
            ['id' => 6,  'label' => 'Stories',         'value' => 'num_of_floors',         'group' => 'active', 'selected' => true,  'sortable' => true],
            ['id' => 7,  'label' => 'Notes',           'value' => 'notes',           'group' => 'active', 'selected' => true,  'sortable' => false],
            ['id' => 8,  'label' => 'SQ FT',         'value' => 'floor_area',    'group' => 'active', 'selected' => true,  'sortable' => true],
            ['id' => 9,  'label' => 'Supervisor',      'value' => 'supervisor',      'group' => 'active', 'selected' => true,  'sortable' => true],
            ['id' => 10, 'label' => 'Purchase Order',  'value' => 'order_number',  'group' => 'active', 'selected' => true,  'sortable' => true],
            ['id' => 11, 'label' => 'Planned Start',   'value' => 'scheduled_start_date',   'group' => 'active', 'selected' => true,  'sortable' => true],
            ['id' => 12, 'label' => 'Address',         'value' => 'address',         'group' => 'active', 'selected' => true,  'sortable' => true],
            ['id' => 13, 'label' => 'Price',           'value' => 'price',           'group' => 'active', 'selected' => true,  'sortable' => true],
            ['id' => 14, 'label' => 'Completion Date', 'value' => 'ended_at', 'group' => 'active', 'selected' => true,  'sortable' => true],
            ['id' => 15, 'label' => 'Order Type', 'value' => 'is_extra', 'group' => 'active', 'selected' => true,  'sortable' => true],
        ];

        $this->dispatchFilters();
        $this->dispatch('update-columns', columns: $this->columns);
    }

    public function render()
    {
        return view('livewire.task.task-progress', [
            'tasks' => $this->tasks,
        ])->layout('layouts.app', [
            'showFilters' => true,
            'showSearch'  => true,
            'showColumns' => true,
            'showAddNew'  => true,
        ]);
    }
}
