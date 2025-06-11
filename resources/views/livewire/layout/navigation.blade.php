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

                <!-- Display currently applied filters -->
                @if ( !empty( $selectedBuilders ) || !empty( $selectedSubdivisions ) || !empty( $selectedLots ) )
                    <div class="flex flex-wrap gap-2 mb-2">
                        @if(!empty($selectedBuilders))
                            <flux:badge color="blue">
                                Builders: {{ implode(', ', $selectedBuilders) }}
                                <flux:badge.close wire:click="resetFilters('builders')" />
                            </flux:badge>
                        @endif
                        @if(!empty($selectedSubdivisions))
                            <flux:badge color="green">
                                Subdivisions: {{ collect($selectedSubdivisions)->implode(', ') }}
                                <flux:badge.close wire:click="resetFilters('subdivisions')" />
                            </flux:badge>
                        @endif
                        @if(!empty($selectedLots))
                            <flux:badge color="amber">
                                Lots: {{ implode(', ', $selectedLots) }}
                                <flux:badge.close wire:click="resetFilters('lots')" />
                            </flux:badge>
                        @endif
                        <flux:badge color="slate" variant="solid">
                            Clear All
                            <flux:badge.close wire:click="resetFilters('all')" />
                        </flux:badge>
                    </div>
                @endif

                <!-- Builders Select -->

                <flux:select multiple searchable clearable  label="Builders" variant="listbox" indicator="checkbox" placeholder="Select the Builders" wire:model="selectedBuilders" clear="close">
                    @foreach($filters['builders'] ?? [] as $builder)
                        <flux:select.option value="{{ $builder['value'] }}">{{ $builder['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <!-- Subdivision's Select -->
                <flux:select multiple searchable clearable label="Subdivisions" variant="listbox" indicator="checkbox" placeholder="Select the Subdivisions" wire:model="selectedSubdivisions" clear="close">
                    @foreach($filters['subdivisions'] ?? [] as $subd)
                        <flux:select.option value="{{ $subd['value'] }}">{{ $subd['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <!-- Lot Number Input -->
                <flux:select multiple searchable clearable label="Lot #" variant="listbox" indicator="checkbox" placeholder="Select the Lot(s)" wire:model="selectedLots" clear="close">
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
        @if($showFilters && ( !empty($selectedBuilders) || !empty($selectedSubdivisions) || !empty($selectedLots) ))
        <div class="px-4 py-2 flex flex-wrap gap-2 border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            @if(!empty($selectedBuilders))
                <flux:badge color="blue">
                    Builders: {{ implode(', ', $selectedBuilders) }}
                    <flux:badge.close wire:click="resetFilters('builders')" />
                </flux:badge>
            @endif
            @if(!empty($selectedSubdivisions))
                <flux:badge color="green">
                    Subdivisions: {{ collect($selectedSubdivisions)->implode(', ') }}
                    <flux:badge.close wire:click="resetFilters('subdivisions')" />
                </flux:badge>
            @endif
            @if(!empty($selectedLots))
                <flux:badge color="amber">
                    Lots: {{ implode(', ', $selectedLots) }}
                    <flux:badge.close wire:click="resetFilters('lots')" />
                </flux:badge>
            @endif
        </div>
        @endif
    </div>
