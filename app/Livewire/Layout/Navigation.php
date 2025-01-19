<?php
    namespace App\Livewire\Layout;

    use Livewire\Component;

    class Navigation extends Component
    {
        public $title;
        public $showAddNew;
        public $showFilters;
        public $showSearch;
        public $showColumns;

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
         * Triggered by "Save changes" button in the Blade (wire:click="applyGlobalFilters").
         * Dispatches "global-filters-updated" so LotProgress or other components can update.
         */
        public function applyGlobalFilters()
        {   
            $this->dispatch('global-filters-updated', [
                'builders' => $this->selectedBuilders,
                'subdivisions' => $this->selectedSubdivisions,
                'lots' => $this->selectedLots,
            ]);
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

        public function mount($showAddNew=false, $showFilters=false, $showSearch=false, $showColumns=false)
        {
            $this->showAddNew = $showAddNew;
            $this->showFilters = $showFilters;
            $this->showSearch = $showSearch;
            $this->showColumns = $showColumns;
        }
    
        public function render()
        {
            // Just render your Blade file
            return view('livewire.layout.navigation', [
                'filters' => $this->filters,
            ]);
        }
    }
