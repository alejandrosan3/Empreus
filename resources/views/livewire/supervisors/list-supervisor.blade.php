<div>
    <flux:table :paginate="$this->supervisors">
        {{-- dynamic headers --}}
        @foreach ($this->columns as $column)
            <flux:table.column
                sortable
                :sorted="$sortBy === '{{ $column }}'"
                :direction="$sortDirection"
                wire:click="sort('{{ $column }}')">
                {{ ucfirst(str_replace('_', ' ', $column)) }}
            </flux:table.column>
        @endforeach

        <flux:table.column>Actions</flux:table.column>

        {{-- rows --}}
        <flux:table.rows>
            @foreach ($this->supervisors as $supervisor)
                <flux:table.row :key="$supervisor->id">
                    @foreach ($this->columns as $column)
                        <flux:table.cell>{{ $supervisor->{$column} }}</flux:table.cell>
                    @endforeach

                    <flux:table.cell>
                        <flux:button variant="ghost" size="sm"
                                    icon="ellipsis-horizontal" inset="top bottom" />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>