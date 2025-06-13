<?php

    namespace App\Livewire\Lot;

    use App\Models\Lot;
    use Illuminate\Support\Facades\Auth;
    use Livewire\Component;
    use Livewire\WithPagination;
    use Livewire\Attributes\On;
    use Livewire\Attributes\Computed;

    class LotProgress extends Component
    {
        use WithPagination;

        // Sort-related
        public $sortBy = 'id';
        public $sortDirection = 'desc';

        // Extra config for the view's table
        public $columns = [];
        public $taskTypes = ['Clean Rough', 'Rough Windows', 'Pressure Wash', 'Floor Machine', 'Reclean #1', 'Reclean #2', 'Final Clean', 'Final Windows'];

        // Selected filters keyed by column name
        public array $selectedFilters = [];

        public $searchTerm = '';

        // multiselected lots get stored here
        public $checkedLots = [];

        // This is a flag used to show either lotprogress or the contentdetails
        public $selectedLot = null;

        // EPO Handling variables
        public $epoOverview = "";
        public $epoCount = "";

        protected $listeners = [
            'contentUpdated' => '$refresh',   // ← built-in Livewire shortcut
            'search-tasks'   => 'updateSearch',
        ];

        /**
         * Sort the table by a given column, toggling sort direction if needed,
         * then recalc filter dropdowns so the user sees updated lists.
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
         * function that sends the event to the modal.
         */
        public function startBulkEdit(): void
        {
            // <livewire:partials.bulk-edit-modal> listens for this
            $this->dispatch('bulk-edit-action', entity: 'lot', ids: $this->checkedLots);
        }

        public function updateSearch($query)
        {
            $this->searchTerm = $query;
            $this->resetPage();
        }


        /**
         * Computed property for retrieving lots based on selected filters + sort.
         */
        public function getLotsProperty()
        {
            if ($this->searchTerm !== '') {
                return Lot::search($this->searchTerm)
                    ->query(function ($query) {
                        $query->where('company_id', session('company_id'));
                        $this->applyLotFilters($query);
                    })
                    ->paginate(20);
            }

            $query = Auth::user()->lotsForCurrentCompany();

            $this->applyLotFilters($query);

            // Sorting logic
            if ($this->sortBy === 'builder') {
                $query
                    ->join('builders', 'lots.builder_id', '=', 'builders.id')
                    ->orderBy('builders.name', $this->sortDirection);
            } elseif ($this->sortBy === 'status') {
                $query
                    ->join('status_types', 'lots.status_type_id', '=', 'status_types.id')
                    ->orderBy('status_types.name', $this->sortDirection);
            } elseif ($this->sortBy === 'lot_number') {
                $query->orderByRaw("CAST(lot_number AS UNSIGNED) {$this->sortDirection}, lot_number ASC");
            } elseif ($this->sortBy === 'supervisor') {
                $query
                    ->join('supervisors', 'lots.supervisor_id', '=', 'supervisors.id')
                    ->orderBy('supervisors.name', $this->sortDirection);
            } elseif ($this->sortBy === 'last_completed_date') {
                $query
                    ->select('lots.*')
                    ->selectRaw('(SELECT MAX(t.ended_at)
                                  FROM tasks t
                                  WHERE t.lot_id = lots.id
                                 ) as last_completed_date')
                    ->groupBy('lots.id')
                    ->orderBy('last_completed_date', $this->sortDirection);
            } else {
                $query->orderBy($this->sortBy, $this->sortDirection);
            }

            return $query->paginate(20);
        }

        private function applyLotFilters($query): void
        {
            foreach ($this->selectedFilters as $column => $values) {
                if (empty($values)) {
                    continue;
                }

                switch ($column) {
                    case 'builder':
                        $query->whereHas('builder', fn($q) => $q->whereIn('id', $values));
                        break;
                    case 'subdivision':
                        $query->where(function ($q) use ($values) {
                            foreach ($values as $filter) {
                                // If Livewire passed the whole option-array, pull out its 'value'
                                $raw = is_array($filter) ? ($filter['value'] ?? '') : $filter;

                                // Now split on the pipe
                                [$subdivision, $phase] = explode('|', $raw);

                                $q->orWhere(fn($q2) => $q2->where('subdivision', $subdivision)
                                                    ->where('phase', $phase));
                            }
                        });
                        break;
                    case 'lot_number':
                        $query->whereIn('lot_number', $values);
                        break;
                    case 'supervisor':
                        $query->whereHas('supervisor', fn($q) => $q->whereIn('id', $values));
                        break;
                    case 'phase':
                        $query->whereIn('phase', $values);
                        break;
                }
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
         * Builds a separate query that grabs the possible Builders/Subdivisions/Lots
         * matching the current selection. Then dispatches them in an event for the UI dropdowns.
         */
        #[On('company-updated')]
        public function dispatchFilters()
        {
            $query = Auth::user()->lotsForCurrentCompany();

            $filters = [];

            foreach ($this->columns as $column) {
                if (empty($column['filterable'])) {
                    continue;
                }
                $colKey = $column['value'];
                $optionsQuery = clone $query;

                foreach ($this->selectedFilters as $filterKey => $values) {
                    if ($filterKey === $colKey || empty($values)) {
                        continue;
                    }
                    switch ($filterKey) {
                        case 'builder':
                            $optionsQuery->whereHas('builder', fn($q) => $q->whereIn('id', $values));
                            break;
                        case 'subdivision':
                            $optionsQuery->where(function ($q) use ($values) {
                                foreach ($values as $val) {
                                    // if Livewire gave us the full ['value'=>…,'label'=>…] array, grab its 'value'
                                    $raw = is_array($val) ? ($val['value'] ?? '') : $val;

                                    // now $raw is guaranteed to be a string like "ARBORS 40 EXP (3A)|3A"
                                    [$subdivision, $phase] = explode('|', $raw);

                                    // and use _those_ two vars down here:
                                    $q->orWhere(fn($q2) => $q2
                                        ->where('subdivision', $subdivision)
                                        ->where('phase',       $phase)
                                    );
                                }
                            });
                            break;
                        case 'lot_number':
                            $optionsQuery->whereIn('lot_number', $values);
                            break;
                        case 'supervisor':
                            $optionsQuery->whereHas('supervisor', fn($q)=>$q->whereIn('id',$values));
                            break;
                        case 'phase':
                            $optionsQuery->whereIn('phase', $values);
                            break;
                    }
                }

                switch ($colKey) {
                    case 'builder':
                        $filters[$colKey] = $optionsQuery->clone()
                            ->join('builders','lots.builder_id','=','builders.id')
                            ->select('builders.id','builders.name')
                            ->distinct()
                            ->orderBy('builders.name','asc')
                            ->get()
                            ->map(fn($b)=>['value'=>$b->id,'label'=>$b->name])
                            ->toArray();
                        break;
                    case 'subdivision':
                        $filters[$colKey] = $optionsQuery->clone()
                            ->select('subdivision','phase')
                            ->groupBy('subdivision','phase')
                            ->orderBy('subdivision','asc')
                            ->get()
                            ->map(fn($i)=>['value'=>$i->subdivision.'|'.$i->phase,'label'=>$i->subdivision.($i->phase ? " ({$i->phase})" : '')])
                            ->toArray();
                        break;
                    case 'lot_number':
                        $filters[$colKey] = $optionsQuery->clone()
                            ->distinct()
                            ->orderByRaw('CAST(lots.lot_number AS UNSIGNED) ASC, lots.lot_number ASC')
                            ->pluck('lot_number')
                            ->toArray();
                        break;
                    case 'supervisor':
                        $filters[$colKey] = $optionsQuery->clone()
                            ->join('supervisors','lots.supervisor_id','=','supervisors.id')
                            ->select('supervisors.id','supervisors.name')
                            ->distinct()
                            ->orderBy('supervisors.name','asc')
                            ->get()
                            ->map(fn($s)=>['value'=>$s->id,'label'=>$s->name])
                            ->toArray();
                        break;
                    case 'phase':
                        $filters[$colKey] = $optionsQuery->clone()
                            ->select('phase')
                            ->distinct()
                            ->orderBy('phase','asc')
                            ->pluck('phase')
                            ->toArray();
                        break;
                }
            }

            $this->dispatch('filters-updated', filters: $filters);
            $this->resetPage();
        }


        /**
         * Re-dispatch filter data if sorting changes.
         * This ensures the dropdowns might reflect the new sort. (Optional)
         */
        public function updated($propertyName)
        {
            // Recalculate and dispatch filters when relevant properties change
            if (in_array($propertyName, ['sortBy', 'sortDirection'])) {
                $this->dispatchFilters();
            }
        }

        /**
         * Load lot details dynamically or redirect to the URL-based route.
         *
         * @param int $lotId
         * @return void
         */
        public function loadDetails($lotId)
        {
            /* Dynamic loading
            if ($this->isRouteRequest()) {
                return request()->routeIs('content-details');
            } *

            // Dynamically load the lot
            $this->selectedLot = Lot::findOrFail($lotId);
            */

            // Dynamic loading
            if (!$this->isRouteRequest()) {
                $this->selectedLot = Lot::findOrFail($lotId);
            } else {
                // URL-based navigation
                return redirect()->route('lot-details', ['lot_id' => $lotId]);
            }
        }

        /**
         * Check if the current request is route-based (manual URL entry).
         *
         * @return bool
         */
        private function isRouteRequest()
        {
            logger()->info('Route Name:', ['route' => optional(request()->route())->getName()]);
            logger()->info('Headers:', ['headers' => request()->headers->all()]);

            // If it's a Livewire request, return false.
            if (request()->header('X-Livewire')) {
                return false;
            }

            // For normal HTTP requests, check the route.
            return request()->routeIs('content-details') || request()->routeIs('lot-progress');
        }

        /**
         * Reset the selected lot and return to the table view.
         *
         * @return void
         */
        #[On('back-button-press')]
        public function resetSelectedLot($selectedLot = null)
        {
            $this->selectedLot = $selectedLot;
        }

        #[On('column-list-updated')]
        public function updateColumns($columns)
        {
            $this->columns = $columns;
        }


        /**
         * On component mount, dispatch initial filter data so the user sees
         * all possible builders/subdivisions/lots in the UI.
         */
        public function mount()
        {

            $this->columns = [
                // Fixed columns (always selected)
                ['id' => 1, 'label' => 'Builder', 'value' => 'builder', 'group' => 'fixed', 'selected' => true, 'sortable' => true, 'editable'=> false, 'filterable' => true, 'color' => 'purple'],
                ['id' => 2, 'label' => 'Subdivision', 'value' => 'subdivision', 'group' => 'fixed', 'selected' => true, 'sortable' => true, 'editable'=> false, 'filterable' => true, 'color' => 'amber'],
                ['id' => 3, 'label' => 'Lot', 'value' => 'lot_number', 'group' => 'fixed', 'selected' => true, 'sortable' => true, 'editable'=> false, 'filterable' => true, 'color' => 'cyan'],
                // Active columns (user can toggle visibility)
                ['id' => 4, 'label' => 'Stories', 'value' => 'stories', 'group' => 'active', 'selected' => false, 'sortable' => false, 'editable'=> true, 'filterable' => false],
                ['id' => 5, 'label' => 'Last Completed Task', 'value' => 'last_completed_date', 'group' => 'active', 'selected' => true, 'sortable' => true, 'editable'=> false],
                ['id' => 6, 'label' => 'Standard Tasks', 'value' => 'standard_po', 'group' => 'active', 'selected' => true, 'sortable' => false, 'editable'=> false],
                ['id' => 7, 'label' => 'EPOs', 'value' => 'epo', 'group' => 'active', 'selected' => true, 'sortable' => false, 'editable'=> false],
                // Additional columns hidden by default
                ['id' => 8,  'label' => 'Address',            'value' => 'address',            'group' => 'active', 'selected' => false, 'sortable' => true,  'editable'=> true, 'filterable' => false],
                ['id' => 9,  'label' => 'Phase',              'value' => 'phase',              'group' => 'active', 'selected' => false, 'sortable' => true,  'editable'=> true, 'filterable' => false, 'color' => 'indigo'],
                ['id' => 10, 'label' => 'Supervisor',         'value' => 'supervisor',         'group' => 'active', 'selected' => false, 'sortable' => true,  'editable'=> true, 'filterable' => true, 'color' => 'rose'],
                ['id' => 11, 'label' => 'SQ FT',              'value' => 'floor_area',         'group' => 'active', 'selected' => false, 'sortable' => true,  'editable'=> true, 'filterable' => false],
            ];

            // Dispatch initial filters when the component loads
            $this->dispatchFilters();

            // Dispatch columns to the sortable col dropdown
            $this->dispatch('update-columns', columns: $this->columns);
        }

        public function epoCount(Lot $lot): array
        {
            $epoTasks = $lot->tasks->where('is_extra', 1);
            $completedCount = $epoTasks->whereNotNull('ended_at')->count();
            $totalCount = $epoTasks->count();

            return [
                'completed' => $completedCount,
                'total'     => $totalCount,
            ];
        }


        public function render()
        {
            return view('livewire.lot.lot-progress', [
                'lots' => $this->lots,
            ])->layout('layouts.app',[
                'showFilters' => true,
                'showSearch'  => true,
                'showColumns' => true,
                'showAddNew' => true,
                'currentAddable'  => 'lot',
            ]);
        }

    }
