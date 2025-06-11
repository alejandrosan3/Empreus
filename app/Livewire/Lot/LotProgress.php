<?php

    namespace App\Livewire\Lot;

    use App\Models\Lot;
    use Illuminate\Support\Facades\Auth;
    use Livewire\Component;
    use Livewire\WithPagination;
    use Livewire\Attributes\On;
    use Livewire\Attributes\Computed;
    use App\Services\FilterOptionsService;

    class LotProgress extends Component
    {
        use WithPagination;

        // Sort-related
        public $sortBy = 'id';
        public $sortDirection = 'desc';

        // Extra config for the view's table
        public $columns = [];
        public $taskTypes = ['Clean Rough', 'Rough Windows', 'Pressure Wash', 'Floor Machine', 'Reclean #1', 'Reclean #2', 'Final Clean', 'Final Windows'];

        // These store the user's chosen filters
        public $selectedBuilders = [];
        public $selectedSubdivisions = [];
        public $selectedLots = [];

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
            $query->filterByBuilders($this->selectedBuilders)
                ->filterBySubdivisions($this->selectedSubdivisions)
                ->filterByLots($this->selectedLots);
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
         * Builds a separate query that grabs the possible Builders/Subdivisions/Lots
         * matching the current selection. Then dispatches them in an event for the UI dropdowns.
         */
        #[On('company-updated')]
        public function dispatchFilters()
        {
            $service  = app(FilterOptionsService::class);
            $options  = $service->lotOptions(
                $this->selectedBuilders,
                $this->selectedSubdivisions,
                $this->selectedLots
            );

            $this->dispatch('filters-updated',
                builders:     $options['builders'],
                subdivisions: $options['subdivisions'],
                lots:         $options['lots'],
            );
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

            if (!empty($this->selectedBuilders) && !is_numeric($this->selectedBuilders[0])) {
                $this->selectedBuilders = \App\Models\Builder::whereIn('name', $this->selectedBuilders)
                    ->pluck('id')
                    ->toArray();
            }

            $this->columns = [
                // Fixed columns (always selected)
                ['id' => 1, 'label' => 'Builder', 'value' => 'builder', 'group' => 'fixed', 'selected' => true, 'sortable' => true, 'editable'=> false],
                ['id' => 2, 'label' => 'Subdivision', 'value' => 'subdivision', 'group' => 'fixed', 'selected' => true, 'sortable' => true, 'editable'=> false],
                ['id' => 3, 'label' => 'Lot', 'value' => 'lot_number', 'group' => 'fixed', 'selected' => true, 'sortable' => true, 'editable'=> false],
                // Active columns (user can toggle visibility)
                ['id' => 4, 'label' => 'Stories', 'value' => 'stories', 'group' => 'active', 'selected' => false, 'sortable' => false, 'editable'=> true],
                ['id' => 5, 'label' => 'Last Completed Task', 'value' => 'last_completed_date', 'group' => 'active', 'selected' => true, 'sortable' => true, 'editable'=> false],
                ['id' => 6, 'label' => 'Standard Tasks', 'value' => 'standard_po', 'group' => 'active', 'selected' => true, 'sortable' => false, 'editable'=> false],
                ['id' => 7, 'label' => 'EPOs', 'value' => 'epo', 'group' => 'active', 'selected' => true, 'sortable' => false, 'editable'=> false],
                // Additional columns hidden by default
                ['id' => 8,  'label' => 'Address',            'value' => 'address',            'group' => 'active', 'selected' => false, 'sortable' => true,  'editable'=> true],
                ['id' => 9,  'label' => 'Phase',              'value' => 'phase',              'group' => 'active', 'selected' => false, 'sortable' => true,  'editable'=> true],
                ['id' => 10, 'label' => 'Supervisor',         'value' => 'supervisor',         'group' => 'active', 'selected' => false, 'sortable' => true,  'editable'=> true],
                ['id' => 11, 'label' => 'SQ FT',              'value' => 'floor_area',         'group' => 'active', 'selected' => false, 'sortable' => true,  'editable'=> true],
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
