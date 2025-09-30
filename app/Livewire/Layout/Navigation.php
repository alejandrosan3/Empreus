<?php
    namespace App\Livewire\Layout;

    use Livewire\Component;
    use Livewire\Attributes\On;
    use Livewire\Attributes\Computed;
    use Flux\Flux;

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

        // Filter options keyed by column name
        public $filters = [];

        // Selected filter values
        public array $selectedFilters = [];

        public array $columns = [];

        #[On('update-columns')]
        public function updateColumns($columns = [])
        {
            $this->columns = $columns;
        }

        #[Computed]
        public function getFilterColorsProperty()
        {
            return collect($this->columns)
                ->filter(fn($c) => ($c['filterable'] ?? false))
                ->mapWithKeys(fn($c) => [$c['value'] => $c['color'] ?? 'blue'])
                ->all();
        }

        /**
         * Called when the "filters-updated" DOM event is dispatched from LotProgress.
         * Merges new data into $this->filters so we can display updated dropdown options.
         */
        public function filtersUpdated($filterData)
        {
            // Update the filters property with the received data (explicitly)
            foreach ($filterData as $column => $options) {
                $this->filters[$column] = $options;
            }
        }


        /**
         * Triggered by "Save changes" button in the Blade (wire:click="applyGlobalFilters").
         * Dispatches "global-filters-updated" so LotProgress or other components can update.
         */
        public function applyGlobalFilters()
        {
            $this->dispatch('global-filters-updated', filters:$this->selectedFilters);
            Flux::modal('filters')->close();
        }

        public function resetFilters($choice="all")
        {
            if ($choice === "all") {
                $this->selectedFilters = [];
            } else {
                $this->selectedFilters[$choice] = [];
            }
            $this->applyGlobalFilters();
        }

        /**
         * Remove a single value from a filter and apply changes.
         */
        public function removeFilterValue($key, $value)
        {
            if (isset($this->selectedFilters[$key])) {
                // Handle both array and string filter values
                if (is_array($this->selectedFilters[$key])) {
                    // For array filters (like multi-select), filter out the value
                    $this->selectedFilters[$key] = array_values(array_filter(
                        $this->selectedFilters[$key],
                        fn($v) => (string)$v !== (string)$value
                    ));

                    if (empty($this->selectedFilters[$key])) {
                        unset($this->selectedFilters[$key]);
                    }
                } else {
                    // For string filters (like date), just remove the entire filter
                    unset($this->selectedFilters[$key]);
                }

                $this->applyGlobalFilters();
            }
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
