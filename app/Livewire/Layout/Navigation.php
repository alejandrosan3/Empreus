<?php
    namespace App\Livewire\Layout;

    use Livewire\Component;

    class Navigation extends Component
    {
        public $title;
        public $showAddNew;
        public $currentAddable;
        public $showFilters;
        public $showSearch;
    public $showColumns;

    // Search input bound via Livewire
    public $searchTerm = '';

        // These lists fill the dropdown options for Builders/Subdivisions/Lots
        public $filters = [
            'builders' => [],
            'subdivisions' => [],
            'lots' => []
        ];

        // The user's selected items (multi-select) bound via wire:model
        public array $selectedBuilders = [];
        public array $selectedSubdivisions = [];
        public array $selectedLots = [];

        /**
         * Called when the "filters-updated" DOM event is dispatched from LotProgress.
         * Merges new data into $this->filters so we can display updated dropdown options.
         */
        public function filtersUpdated($filterData)
        {
        
            // Update the filters property with the received data
            $this->filters = array_merge($this->filters, $filterData);
        }


        /**
         * Dispatches the "global-filters-updated" event with the current filter
         * selections so other components can react instantly.
         */
        public function applyGlobalFilters()
        {
            $this->dispatch('global-filters-updated', [
                'builders' => $this->selectedBuilders,
                'subdivisions' => $this->selectedSubdivisions,
                'lots' => $this->selectedLots,
            ]);
        }

        /**
         * Automatically apply filters whenever the selected Builders list is
         * updated.
         */
        public function updatedSelectedBuilders()
        {
            $this->applyGlobalFilters();
        }

        /**
         * Automatically apply filters whenever the selected Subdivisions list is
         * updated.
         */
        public function updatedSelectedSubdivisions()
        {
            $this->applyGlobalFilters();
        }

        /**
         * Automatically apply filters whenever the selected Lots list is
         * updated.
         */
        public function updatedSelectedLots()
        {
            $this->applyGlobalFilters();
        }

        public function resetFilters($choice="all")
        {
            switch ($choice) {
                case "subdivisions":
                    $this->selectedSubdivisions = [];
                    break;
                case "builders":
                    $this->selectedBuilders = [];
                    break;
                case "lots":
                    $this->selectedLots = [];
                    break;
                default:
                    $this->selectedSubdivisions = [];
                    $this->selectedBuilders = [];
                    $this->selectedLots = [];
                    break;
            }
            $this->applyGlobalFilters();
        }

        /**
         * Dispatch the search query so pages can react.
         */
        public function submitSearch()
        {
            $this->dispatch('search-tasks', query: $this->searchTerm);
        }

        public function mount($showAddNew=false, $showFilters=false, $showSearch=false, $showColumns=false, $currentAddable="task" )
        {
            $this->showAddNew = $showAddNew;
            $this->showFilters = $showFilters;
            $this->showSearch = $showSearch;
            $this->showColumns = $showColumns;
            $this->currentAddable = $currentAddable;
        }
    
        public function render()
        {
            // Just render your Blade file
            return view('livewire.layout.navigation', [
                'filters' => $this->filters,
            ]);
        }
    }
