<div>
    <div class="flex justify-end">
        <flux:tabs variant="segmented">
            <flux:tab icon="squares-2x2" :active="$activeTab==='board'" wire:click="setTab('board')">Board</flux:tab>
            <flux:tab icon="calendar" :active="$activeTab==='calendar'" wire:click="setTab('calendar')">Calendar</flux:tab>
            <flux:tab icon="map" :active="$activeTab==='map'" wire:click="setTab('map')">Map</flux:tab>
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

    <div class="mt-4">
        @if($activeTab==='board')
            <livewire:task.task-board :$range /> 
        @elseif($activeTab==='calendar')
            <livewire:task.task-calendar :$range :range-days="$rangeDays" />
        @elseif($activeTab==='map')
            <livewire:maps.lot-map :$range :key="'lot-map-'. $range->start()->format('Ymd'). '-'. $range->end()->format('Ymd')"/>
        @endif
    </div>
</div>