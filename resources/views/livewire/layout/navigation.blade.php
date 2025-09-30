    <div x-on:filters-updated.window="$wire.filtersUpdated($event.detail.filters)">
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

                <flux:modal name="filters" class="md:w-[48rem] space-y-6">
                    <div>
                        <flux:heading size="lg">Filters</flux:heading>
                        <flux:subheading>Set the desired filter. Leave blank to ignore.</flux:subheading>
                    </div>

                    <!-- Display currently applied filters -->
                    @if(collect($selectedFilters)->filter()->isNotEmpty())
                        <div class="flex flex-wrap gap-2 mb-2">
                            @foreach($selectedFilters as $key => $vals)
                                @continue(empty($vals))
                                {{ collect($columns)->firstWhere('value', $key)['label'] ?? ucfirst(str_replace('_',' ', $key)) }}:
                                @foreach((array)$vals as $val)
                                    @php
                                        $label = collect($filters[$key] ?? [])
                                            ->firstWhere('value', $val)['label'] ?? $val;
                                        $color = $this->filterColors[$key] ?? 'blue';
                                    @endphp
                                    <flux:tooltip :content="$label">
                                        <flux:badge :color="$color">
                                            {{ $label }}
                                            <flux:badge.close wire:click="removeFilterValue('{{ $key }}','{{ $val }}')" />
                                        </flux:badge>
                                    </flux:tooltip>
                                @endforeach
                                <flux:separator vertical />
                            @endforeach
                            <flux:badge color="slate" variant="solid">
                                Clear All
                                <flux:badge.close wire:click="resetFilters('all')" />
                            </flux:badge>
                        </div>
                    @endif

                    <div class="grid md:grid-cols-2 gap-4">
                        @foreach($columns as $column)
                            @continue(empty($column['filterable']))
                            @php
                                $name    = $column['value'];
                                $label   = $column['label'];
                                $options = $filters[$name] ?? [];
                            @endphp

                            @switch($column['filterType'] ?? 'select')
                                {{-- Date picker --}}
                                @case('date')
                                    <flux:date-picker class="size-auto"
                                                      wire:model="selectedFilters.{{ $name }}"
                                                      label="{{ $label }}"
                                                      placeholder="— select date —"
                                                      with-today>
                                        <x-slot name="trigger">
                                            <flux:date-picker.input />
                                        </x-slot>
                                    </flux:date-picker>
                                    @break

                                {{-- Multi-select listbox --}}
                                @default
                                    <flux:select
                                        multiple
                                        searchable
                                        clearable
                                        label="{{ $label }}"
                                        variant="listbox"
                                        indicator="checkbox"
                                        placeholder="Select"
                                        wire:model="selectedFilters.{{ $name }}"
                                        clear="close"
                                    >
                                        @foreach($options as $option)
                                            <flux:select.option
                                                value="{{ is_array($option) ? $option['value'] : $option }}"
                                            >
                                                {{ is_array($option) ? $option['label'] : $option }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                            @endswitch
                        @endforeach
                    </div>

                    <div class="flex">
                        <flux:spacer />
                        <flux:button type="submit" variant="primary" wire:click="applyGlobalFilters">
                            Save changes
                        </flux:button>
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
        @if($showFilters && collect($selectedFilters)->filter()->isNotEmpty())
        <div class="px-4 py-2 flex flex-wrap gap-2 border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            @foreach($selectedFilters as $key => $vals)
                @continue(empty($vals))
                {{ucfirst(str_replace('_',' ', $key))}}:
                @foreach((array)$vals as $val)
                    @php
                        $label = collect($filters[$key] ?? [])
                            ->firstWhere('value', $val)['label'] ?? $val;
                        $color = $this->filterColors[$key] ?? 'blue';
                    @endphp
                    <flux:tooltip :content="ucfirst(str_replace('_',' ', $key))">
                        <flux:badge :color="$color">
                            {{ $label }}
                            <flux:badge.close wire:click="removeFilterValue('{{ $key }}','{{ $val }}')" />
                        </flux:badge>
                    </flux:tooltip>
                @endforeach
                <flux:separator vertical />

            @endforeach
            <flux:badge color="slate" variant="solid">
                Clear All Filters
                <flux:badge.close wire:click="resetFilters('all')" />
            </flux:badge>
        </div>
        @endif
    </div>
