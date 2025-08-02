<div>
        {{-- Tabs --}}
        <div class="flex justify-end">
            <flux:tabs wire:model.live="activeTab" variant="segmented">
                <flux:tab name="board" icon="squares-2x2">Board</flux:tab>
                <flux:tab name="calendar" icon="calendar">Calendar</flux:tab>
                <flux:tab name="map" icon="map">Map</flux:tab>
            </flux:tabs>
        </div>

    {{-- Calendar Navigation --}}
        <div class="flex justify-between items-center my-6 bg-white dark:bg-zinc-900 p-4 rounded-lg shadow">
            <div class="flex items-center gap-2">
                <flux:button
                    wire:click="previousRange"
                    icon="chevron-left"
                    variant="subtle"
                    title="Previous Range"
                    wire:loading.attr="disabled"
                    wire:target="previousRange"
                >
                    <span wire:loading.remove wire:target="previousRange">
                        Previous
                    </span>
                    <span wire:loading wire:target="previousRange">
                        Loading...
                    </span>
                </flux:button>

                <flux:button
                    wire:click="today"
                    :disabled="$this->isCurrentRange()"
                    wire:loading.attr="disabled"
                    wire:target="today"
                >
                    <span wire:loading.remove wire:target="today">
                        Today
                    </span>
                    <span wire:loading wire:target="today">
                        Loading...
                    </span>
                </flux:button>

                <flux:button
                    wire:click="nextRange"
                    icon="chevron-right"
                    variant="subtle"
                    title="Next Range"
                    wire:loading.attr="disabled"
                    wire:target="nextRange"
                >
                    <span wire:loading.remove wire:target="nextRange">
                        Next
                    </span>
                    <span wire:loading wire:target="nextRange">
                        Loading...
                    </span>
                </flux:button>

                <span class="text-lg font-medium text-gray-700 dark:text-gray-200 ml-2">
                    {{ $currentRangeStart->format('F Y') }}
                </span>

            </div>

            <div class="text-lg font-semibold">
                <flux:date-picker
                    mode="range"
                    wire:model.live="range"
                    start-day="1"
                    presets="today yesterday thisWeek"
                    min-range="1"
                    max-range="7"
                />
            </div>
        </div>

    @php
            // quick helper to count all tasks across statuses - so we can correctly update the
            $tasksCount = array_sum(array_map('count', $tasks));
            $calTasksCount = array_sum(array_map('count', $tasksByDay));
        @endphp

    {{-- Tab Content --}}
        @if ($activeTab === 'board')
            <livewire:task.task-board :$range :$tasks :$hasMore :$howManyMore :$statuses :key="'board-'. $range->start(). '-'. $range->end().'-'.$refreshKey" />
        @endif

    @if ($activeTab === 'calendar')
        <livewire:task.task-calendar :$range :$tasks :$hasMore :$howManyMore :$statuses :$tasksByDay :$rangeDays :key="'calendar-'. $range->start(). '-'. $range->end().'-'.$refreshKey"/>
    @endif

    @if ($activeTab === 'map')
        <livewire:maps.lot-map :$range :$lots :$subdivisions :$howManyMore :key="'map-'. $range->start(). '-'. $range->end()" />
    @endif

    {{-- Centralized Scripts --}}
    @script
    <script>
        console.log('TaskScheduler: Initializing centralized scripts');

        (() => {
            let boardInitialized = false;
            let calendarInitialized = false;
            let mapInitialized = false;

            // Debounce helpers
            let boardTimeout, calendarTimeout, mapTimeout;

            // Board initialization
            const initBoard = () => {
                if (boardTimeout) clearTimeout(boardTimeout);
                boardTimeout = setTimeout(() => {
                    const boardElements = document.querySelectorAll('.taskBoard.sortable:not([data-sortable-init])');
                    if (boardElements.length === 0) return;

                    console.log('🎯 Initializing Board (', boardElements.length, 'elements)');

                    boardElements.forEach(column => {
                        column.dataset.sortableInit = '1';

                        new Sortable(column, {
                            group: { name: 'kanban', pull: true, put: true },
                            animation: 150,
                            draggable: '.cursor-move',
                            onEnd(event) {
                                const taskId = event.item.dataset.id;
                                const newStatus = event.to.dataset.status;
                                const originalStatus = event.from.dataset.status;

                                if (newStatus !== originalStatus) {
                                    Livewire.dispatchTo('task.task-scheduler','taskStatusUpdated', [taskId, newStatus]);
                                }

                                if (event.from.querySelectorAll('.cursor-move').length === 0) {
                                    Livewire.dispatch('load-more', [event.from.dataset.status, null]);
                                }
                            },
                        });
                    });
                }, 16);
            };

            // Calendar initialization
            const initCalendar = () => {
                if (calendarTimeout) clearTimeout(calendarTimeout);
                calendarTimeout = setTimeout(() => {
                    const calendarElements = document.querySelectorAll('.calendarColumn.sortable:not([data-sortable-init])');
                    if (calendarElements.length === 0) return;

                    console.log('📅 Initializing Calendar (', calendarElements.length, 'elements)');

                    calendarElements.forEach(column => {
                        column.dataset.sortableInit = '1';

                        new Sortable(column, {
                            group: { name: 'calendar-tasks', pull: true, put: true },
                            animation: 150,
                            draggable: '.task-item',
                            ghostClass: 'opacity-50',
                            chosenClass: 'bg-gray-100',
                            onEnd(evt) {
                                const taskId = evt.item.dataset.taskId;
                                const newDate = evt.to.dataset.date;
                                const oldDate = evt.from.dataset.date;

                                if (taskId && newDate && oldDate !== newDate) {
                                    Livewire.dispatchTo('task.task-scheduler','taskDateUpdated', [taskId, newDate]);
                                }
                                if (evt.from.querySelectorAll('.cursor-move').length === 0) {
                                    Livewire.dispatch('load-more', [null, oldDate]);
                                }

                            }
                        });
                    });
                }, 16);
            };

            // Map initialization
            const initMap = async () => {
                if (mapTimeout) clearTimeout(mapTimeout);
                mapTimeout = setTimeout(async () => {
                    const mapElement = document.getElementById('lot-map');
                    if (!mapElement) return;

                    console.log('🗺️ Initializing Map');

                    try {
                        const { default: mapInit } = await import('{{ Vite::asset('resources/js/lot-map.js') }}');
                        mapInit();
                    } catch (error) {
                        console.error('Map initialization failed:', error);
                    }
                }, 16);
            };

            // Smart initializer - only runs for active tab
            const initializeActiveTab = () => {
                const activeTab = @this.activeTab;

                console.log('🔄 Active tab:', activeTab);

                switch(activeTab) {
                    case 'board':
                        initBoard();
                        break;
                    case 'calendar':
                        initCalendar();
                        break;
                    case 'map':
                        initMap();
                        break;
                }
            };

            // Event listeners
            document.addEventListener('DOMContentLoaded', initializeActiveTab);

            // Listen for Livewire updates, but be smart about it
            Livewire.hook('morph.added', () => {
                console.log('🔄 morph.added - reinitializing active tab');
                initializeActiveTab();
            });

            // Listen for tab changes
            window.addEventListener('livewire:init', () => {
                Livewire.on('activeTab', (tab) => {
                    console.log('🎯 Tab changed to:', tab);
                    setTimeout(initializeActiveTab, 50); // Small delay for DOM update
                });
            });

            // Custom events
            document.addEventListener('taskDateUpdated', initializeActiveTab);

        })();
    </script>
    @endscript

</div>
