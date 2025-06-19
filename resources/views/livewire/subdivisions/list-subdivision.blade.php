<div>
    <livewire:partials.editable-modal />

    <flux:table :paginate="$this->subdivisions">

        {{-- column headers --}}
        @foreach ($this->columns as $column)
            <flux:table.column
                sortable
                :direction="'{{ $sortDirection }}'"
                :sorted="$sortBy === '{{ $column }}'"
                wire:click="sort('{{ $column }}')"
            >
                {{ ucfirst(str_replace('_', ' ', $column)) }}
            </flux:table.column>
        @endforeach


        {{-- <flux:table.column>Actions</flux:table.column> --}}

        {{-- rows --}}
        <flux:table.rows>
            @foreach ($this->subdivisions as $row)
                <flux:table.row :key="$row->id">
                    <flux:table.cell
                        x-on:dblclick="
                            $dispatch('editField', {
                                fieldId: {{ $row->id }},
                                currentValue: '{{ addslashes($row->subdivision ?? '') }}',
                                fieldType: 'input',
                                optionKey: '',
                                modelClass: '{{ addslashes(\App\Models\Lot::class) }}',
                                modelAttribute: 'subdivision',
                                editable: true
                            })"
                    >
                        {{ $row->subdivision }}
                    </flux:table.cell>
                    {{--
                    <flux:table.cell>
                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"/>
                    </flux:table.cell>
                    --}}
                </flux:table.row>
            @endforeach
        </flux:table.rows>

    </flux:table>
</div>
