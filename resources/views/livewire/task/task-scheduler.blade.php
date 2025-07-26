<div>
    <flux:tab.group>
        <div class="flex justify-end">
            <flux:tabs wire:model.live="activeTab" variant="segmented">
                <flux:tab name="board" icon="squares-2x2">Board</flux:tab>
                <flux:tab name="calendar" icon="calendar">Calendar</flux:tab>
                <flux:tab name="map" icon="map">Map</flux:tab>
            </flux:tabs>
        </div>

        {{dump($activeTab)}}

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

        <flux:tab.panel name="board"> 
            <livewire:task.task-board :$range :key="'taskBoard'. $range->start()->format('Ymd'). '-'. $range->end()->format('Ymd')" />
        </flux:tab.panel>
        
        <flux:tab.panel name="calendar">
            <livewire:task.task-calendar :$range :range-days="$rangeDays" />
        </flux:tab.panel>

        <flux:tab.panel name="map">
            <livewire:maps.lot-map :$range :key="'lot-map-'. $range->start()->format('Ymd'). '-'. $range->end()->format('Ymd')"/>
        </flux:tab.panel>
        
    </flux:tab.group>
</div>