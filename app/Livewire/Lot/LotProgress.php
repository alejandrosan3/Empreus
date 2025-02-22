<?php

    namespace App\Livewire\Lot;

    use App\Models\Lot;
    use Illuminate\Support\Facades\Auth;
    use Livewire\Component;
    use Livewire\WithPagination;
    use Livewire\Attributes\On;

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

        // This is a flag used to show either lotprogress or the contentdetails
        public $selectedLot = null;

        // EPO Handling variables
        public $epoOverview = "";
        public $epoCount = "";


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
         * Computed property for retrieving lots based on selected filters + sort.
         */
        #[\Livewire\Attributes\Computed]
        public function getLotsProperty()
        {
            $query = Auth::user()->lotsForCurrentCompany();

            // Apply filter logic
            if (!empty($this->selectedBuilders)) {
                $query->whereHas('builder', function ($q) {
                    $q->whereIn('name', $this->selectedBuilders);
                });
            }

            if (!empty($this->selectedSubdivisions)) {
                $query->where(function($q) {
                    foreach ($this->selectedSubdivisions as $filter) {
                        [$subdivision, $phase] = explode('|', $filter);
                        $q->orWhere(function($q2) use ($subdivision, $phase) {
                            $q2->where('subdivision', $subdivision)
                               ->where('phase', $phase);
                        });
                    }
                });
            }

            if (!empty($this->selectedLots)) {
                $query->whereIn('lot_number', $this->selectedLots);
            }

            // Sorting logic
            if ($this->sortBy === 'builder') {
                $query
                    ->join('builders', 'lots.builder_id', '=', 'builders.id')
                    ->orderBy('builders.name', $this->sortDirection);
            } elseif ($this->sortBy === 'status') {
                $query
                    ->join('status_types', 'lots.status_type_id', '=', 'status_types.id')
                    ->orderBy('status_types.name', $this->sortDirection);
            }elseif ($this->sortBy === 'lot_number') {
                $query->orderByRaw("CAST(lot_number AS UNSIGNED) {$this->sortDirection}, lot_number ASC");
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


            $this->dispatchFilters();
        }


        /**
         * Builds a separate query that grabs the possible Builders/Subdivisions/Lots
         * matching the current selection. Then dispatches them in an event for the UI dropdowns.
         */
        protected function dispatchFilters()
        {
            $query = Auth::user()->lotsForCurrentCompany();

            if (!empty($this->selectedBuilders)) {
                $query->whereHas('builder', function ($q) {
                    $q->whereIn('name', $this->selectedBuilders);
                });
            }
            if (!empty($this->selectedSubdivisions)) {
                $query->where(function($q) {
                    foreach ($this->selectedSubdivisions as $filter) {
                        // split the composite value
                        [$subdivision, $phase] = explode('|', $filter);
                        $q->orWhere(function($q2) use ($subdivision, $phase) {
                            $q2->where('subdivision', $subdivision)
                               ->where('phase', $phase);
                        });
                    }
                });
            }
            if (!empty($this->selectedLots)) {
                $query->whereIn('lot_number', $this->selectedLots);
            }

            // For re-populating select options
            $builders = $query->clone()
                ->join('builders', 'lots.builder_id', '=', 'builders.id')
                ->distinct()
                ->orderby('builders.name', 'asc')
                ->pluck('builders.name');

            $subdivisions = $query->clone()
                ->select('subdivision', 'phase')
                ->groupBy('subdivision', 'phase')
                ->orderby('subdivision', 'asc')
                ->get()
                ->map(function ($item) {
                    return [
                        'value' => $item['subdivision'] . '|' . $item['phase'],
                        'label' => $item['subdivision'] . ($item['phase'] ? " ({$item['phase']})" : ""),
                    ];
                })
                ->toArray();

            $lots = $query->clone()
                ->distinct()
                ->orderby('lot_number', 'asc')
                ->pluck('lot_number');

            // Dispatch the filter data as a browser event (for the dropdowns):
            $this->dispatch('filters-updated',
                builders: $builders,
                subdivisions: $subdivisions,
                lots: $lots
            );
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
                return redirect()->route('content-details', ['lot_id' => $lotId]);
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
                ['id' => 1, 'label' => 'Builder', 'value' => 'builder', 'group' => 'fixed', 'selected' => true, 'sortable' => true],
                ['id' => 2, 'label' => 'Subdivision', 'value' => 'subdivision', 'group' => 'fixed', 'selected' => true, 'sortable' => true],
                ['id' => 3, 'label' => 'Lot', 'value' => 'lot_number', 'group' => 'fixed', 'selected' => true, 'sortable' => true],
                // Active columns (user can toggle visibility)
                ['id' => 4, 'label' => 'Stories', 'value' => 'stories', 'group' => 'active', 'selected' => false, 'sortable' => false],
                ['id' => 5, 'label' => 'Last Completed Task', 'value' => 'last_completed_date', 'group' => 'active', 'selected' => true, 'sortable' => true],
                ['id' => 6, 'label' => 'Standard Tasks', 'value' => 'standard_po', 'group' => 'active', 'selected' => true, 'sortable' => false],
                ['id' => 7, 'label' => 'EPOs', 'value' => 'epo', 'group' => 'active', 'selected' => true, 'sortable' => false],
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
                'showSearch'  => false,
                'showColumns' => true,
                'showAddNew' => false,]);
        }

    }
