<?php

namespace App\Livewire\Lot;

use App\Models\Lot;
use App\Models\StatusType;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Repositories\LotProgressRepository;
use App\Traits\WithToast;
use App\Services\ColumnPreferenceService;

class LotProgress extends Component
{
    use WithPagination, WithToast;

        // Sort-related
        public $sortBy = 'id';
        public $sortDirection = 'desc';

        // Extra config for the view's table
        public $columns = [];
        public $taskTypes = ['Clean Rough', 'Rough Windows', 'Pressure Wash', 'Floor Machine', 'Reclean #1', 'Reclean #2', 'Final Clean', 'Final Windows'];

        // Selected filters keyed by column name
        public $selectedFilters = [];

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

        protected LotProgressRepository $repository;
        protected ColumnPreferenceService $columnPreferences;

        public function boot(LotProgressRepository $repo, ColumnPreferenceService $columnPreferenceService)
        {
            $this->repository = $repo;
            $this->columnPreferences = $columnPreferenceService;
        }

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
            return $this->repository->getLots(
                $this->selectedFilters,
                $this->sortBy,
                $this->sortDirection,
                $this->searchTerm
            );
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
            $filters = $this->repository->filterOptions($this->selectedFilters, $this->columns);

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
            $this->columnPreferences->setColumns(auth()->user(), 'lot-progress', $columns);
        }

        /**
        ** Duplicate a Lot by its ID with its relations.
        */
        public function duplicateLot(int $lotId): void
        {
            try {
                $original = Lot::with('tasks')->findOrFail($lotId);

                // find the Pending status ID once
                $pendingId = StatusType::where('name', 'Pending')->value('id');

                // duplicate the lot + its tasks, overriding each cloned task to Pending
                $copy = $original->duplicateWith(
                    overrideAttributes: [],                  // no changes on the lot itself
                    relations: ['tasks'],          // clone the hasMany tasks
                    relationOverrides: [
                        'tasks' => ['status_type_id' => $pendingId],
                    ]
                );

                $this->showSuccess("Lot #{$original->id} cloned as #{$copy->id}, tasks set to Pending");
                $this->dispatch('contentUpdated');
            } catch (\Throwable $e) {
                $this->showError("Could not duplicate Lot: {$e->getMessage()}");
            }
        }


        /**
         * On component mount, dispatch initial filter data so the user sees
         * all possible builders/subdivisions/lots in the UI.
         */
        public function mount(LotProgressRepository $repository)
        {
            // If no lot, jump to create view
            if (Lot::count() === 0) {
                $this->redirectRoute('lot-create');
            }
            $this->repository = $repository;

            $this->columns       = $this->columnPreferences->getColumns(auth()->user(), 'lot-progress');
            $this->sortBy        = config('lot-progress.default_sort_by');
            $this->sortDirection = config('lot-progress.default_sort_direction');

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
