<div>
    <livewire:partials.editable-modal />

    <flux:table :paginate="$this->workers">
        @foreach ($this->columns as $column)
            <flux:table.column
                sortable
                :sorted="$sortBy === '{{ $column }}'"
                :direction="$sortDirection"
                wire:click="sort('{{ $column }}')">
                {{ ucfirst(str_replace('_', ' ', $column)) }}
            </flux:table.column>
        @endforeach

        <flux:table.rows>
            @foreach ($this->workers as $worker)
                <flux:table.row :key="$worker->id">
                    @foreach ($this->columns as $column)
                        <flux:table.cell
                            x-on:dblclick="
                                $dispatch('editField', {
                                    fieldId: {{ $worker->id }},
                                    currentValue: '{{ addslashes($worker->{$column} ?? '') }}',
                                    fieldType: 'input',
                                    optionKey: '',
                                    modelClass: '{{ addslashes(\App\Models\User::class) }}',
                                    modelAttribute: '{{ $column }}',
                                    editable: true
                                })"
                        >
                            {{ $worker->{$column} }}
                        </flux:table.cell>
                    @endforeach
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
