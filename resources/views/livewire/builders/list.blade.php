<div>
    <livewire:partials.editable-modal />

    <flux:table :paginate="$this->builders">
    @foreach ($this->columns as $column)
        <flux:table.column
            sortable
            :sorted="$sortBy === '{{ $column }}'"
            :direction="$sortDirection"
            wire:click="sort('{{ $column }}')">
            {{ ucfirst(str_replace('_', ' ', $column)) }}
        </flux:table.column>
    @endforeach
    {{-- <flux:table.column>Actions</flux:table.column> --}}

    <flux:table.rows>
        @foreach ($this->builders as $builder)
            <flux:table.row :key="$builder->id">
                @foreach ($this->columns as $column)
                    <flux:table.cell
                        x-on:dblclick="
                            $dispatch('editField', {
                                fieldId: {{ $builder->id }},
                                currentValue: '{{ addslashes($builder->{$column} ?? '') }}',
                                fieldType: 'input',
                                optionKey: '',
                                modelClass: '{{ addslashes(\App\Models\Builder::class) }}',
                                modelAttribute: '{{ $column }}',
                                editable: true
                            })"
                    >
                        {{ $builder->{$column} }}
                    </flux:table.cell>
                @endforeach

                {{--
                <flux:table.cell>
                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>
                </flux:table.cell>
                --}}
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>
</div>
