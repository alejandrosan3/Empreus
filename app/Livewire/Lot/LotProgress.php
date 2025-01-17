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
        public $taskTypes = ['Clean Rough', 'Pressure Wash', 'Floor Machine', 'Reclean #1', 'Reclean #2'];

        // These store the user's chosen filters
        public $selectedBuilders = [];
        public $selectedSubdivisions = [];
        public $selectedLots = [];


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
                $query->whereIn('subdivision', $this->selectedSubdivisions);
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
            } else {
                $query->orderBy($this->sortBy, $this->sortDirection);
            }

            return $query->paginate(10);
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
                $query->whereIn('subdivision', $this->selectedSubdivisions);
            }
            if (!empty($this->selectedLots)) {
                $query->whereIn('lot_number', $this->selectedLots);
            }
        
            // For re-populating select options
            $builders = $query->clone()->join('builders', 'lots.builder_id', '=', 'builders.id')
                            ->distinct()
                            ->pluck('builders.name');
            $subdivisions = $query->clone()->distinct()->pluck('subdivision');
            $lots = $query->clone()->distinct()->pluck('lot_number');
        
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
         * On component mount, dispatch initial filter data so the user sees
         * all possible builders/subdivisions/lots in the UI.
         */
        public function mount()
        {
            // Dispatch initial filters when the component loads
            $this->dispatchFilters();
        }
        
        public function render()
        {

            return view('livewire.lot.lot-progress', [
                'lots' => $this->lots,
            ])->layout('layouts.app',[
                'showFilters' => true,
                'showSearch'  => true,
                'showColumns' => true,
                'showAddNew' => true,]);
        }

    }
