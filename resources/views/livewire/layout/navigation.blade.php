<div x-on:filters-updated.window="$wire.filtersUpdated($event.detail)">
    <flux:navbar class="flex justify-between">

        @if($showAddNew)
        <!-- "Add new element" button -->
        <flux:tooltip content="Add new X">
            <flux:button icon="plus"></flux:button>
        </flux:tooltip>
        @endif

        @if($showFilters)
        <!-- "Filters" button -->
        <flux:modal.trigger name="filters">
            <flux:tooltip content="Filters">
                <flux:button icon="adjustments-horizontal"></flux:button>
            </flux:tooltip>
        </flux:modal.trigger>

        <flux:modal name="filters" class="md:w-96 space-y-6">
            <div>
                <flux:heading size="lg">Filters</flux:heading>
                <flux:subheading>Set the desired filter. Leave blank to ignore.</flux:subheading>
            </div>

            <!-- Builders Select -->
            <flux:select label="Builders" variant="listbox" searchable indicator="checkbox" multiple placeholder="Select the Builders" wire:model="selectedBuilders">
                @foreach($filters['builders'] ?? [] as $builder)
                    <flux:option>{{ $builder }}</flux:option>
                @endforeach
            </flux:select>

            <!-- Subdivision's Select -->
            <flux:select label="Subdivisions" variant="listbox" searchable indicator="checkbox" multiple placeholder="Select the Subdivisions" wire:model="selectedSubdivisions" >
                @foreach($filters['subdivisions'] ?? [] as $subdivision)
                    <flux:option>{{ $subdivision }}</flux:option>
                @endforeach
            </flux:select>

            <!-- Lot Number Input -->
            <flux:select label="Lot #" variant="listbox" searchable indicator="checkbox" multiple placeholder="Select the Lot(s)" wire:model="selectedLots" >
                @foreach($filters['lots'] ?? [] as $lot)
                    <flux:option>{{ $lot }}</flux:option>
                @endforeach
            </flux:select>

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary" wire:click="applyGlobalFilters">Save changes</flux:button>
            </div>
        </flux:modal>
        @endif

        @if($showSearch)
        <!-- "Search" Input -->
        <flux:input placeholder="Search..." icon="magnifying-glass" class="mx-8" />
        @endif

        @if($showColumns)
        <!-- "Rearrange, Edit and Freeze columns" button -->
        <livewire:layout.edit-columns-dropdown />
        @endif
    </flux:navbar>
</div>