    <div x-on:filters-updated.window="$wire.filtersUpdated($event.detail)">
        <flux:navbar class="flex justify-between">

            @if($showAddNew)
            <!-- "Add new element" button -->
                <x-add-button :type="$currentAddable" />
            @endif


            @if($showFilters)
            <!-- "Filters" button -->
            <flux:modal.trigger name="filters">
                <flux:tooltip content="Filters">
                    <flux:button icon="adjustments-horizontal"></flux:button>
                </flux:tooltip>
            </flux:modal.trigger>

            <flux:modal name="filters" class="md:w-96 space-y-6">
                <div class=>
                    <flux:heading size="lg">Filters</flux:heading>
                    <flux:subheading>Set the desired filter. Leave blank to ignore.</flux:subheading>
                </div>

                <!-- Reset all filters section -->

                @if ( !empty( $selectedBuilders ) || !empty( $selectedSubdivisions ) || !empty( $selectedLots ) )
                    <div class="filter-alert bg-slate-100 dark:bg-zinc-600 w-full">
                    <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters('all')" class="dark:text-black">Remove All Filters</flux:button>
                    </div>
                @endif

                <!-- Builders Select -->

                <flux:select multiple searchable clearable  label="Builders" variant="listbox" indicator="checkbox" placeholder="Select the Builders" wire:model="selectedBuilders" clear="close">
                    @if ( !empty( $selectedBuilders ) ) <flux:button variant="ghost" icon="x-mark"  wire:click="resetFilters('builders')">Remove Filter</flux:button> @endif
                    @foreach($filters['builders'] ?? [] as $builder)
                        <flux:select.option>{{ $builder }}</flux:select.option>
                    @endforeach
                </flux:select>

                <!-- Subdivision's Select -->
                <flux:select multiple searchable clearable label="Subdivisions" variant="listbox" indicator="checkbox" placeholder="Select the Subdivisions" wire:model="selectedSubdivisions" clear="close">
                    @if ( !empty( $selectedSubdivisions ) ) <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters('subdivisions')">Remove Filter</flux:button> @endif
                    @foreach($filters['subdivisions'] ?? [] as $subd)
                        <flux:select.option value="{{ $subd['value'] }}">{{ $subd['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <!-- Lot Number Input -->
                <flux:select multiple searchable clearable label="Lot #" variant="listbox" indicator="checkbox" placeholder="Select the Lot(s)" wire:model="selectedLots" clear="close">
                    @if ( !empty( $selectedLots ) ) <flux:button variant="ghost" icon="x-mark" wire:click="resetFilters('lots')">Remove Filter</flux:button> @endif
                    @foreach($filters['lots'] ?? [] as $lot)
                        <flux:select.option>{{ $lot }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary" wire:click="applyGlobalFilters">Save changes</flux:button>
                </div>
            </flux:modal>
            @endif

            @if($showSearch)
            <!-- "Search" Input handled by Livewire -->
            <flux:input
                wire:model.defer="searchTerm"
                wire:keydown.enter.prevent="submitSearch"
                placeholder="Search..."
                icon="magnifying-glass"
                class="mx-8"
            />
            @endif

            @if($showColumns)
            <!-- "Rearrange, Edit and Freeze columns" button -->
            <livewire:layout.edit-columns-dropdown />
            @endif
        </flux:navbar>
    </div>
