<flux:table :paginate="$this->builders">
    @foreach ($this->columns as $column)
        <flux:column 
            sortable 
            :sorted="$sortBy === '{{ $column }}'" 
            :direction="$sortDirection" 
            wire:click="sort('{{ $column }}')">
            {{ ucfirst(str_replace('_', ' ', $column)) }}
        </flux:column>
    @endforeach
    <flux:column>Actions</flux:column>

    <flux:rows>
        @foreach ($this->builders as $builder)
            <flux:row :key="$builder->id">
                @foreach ($this->columns as $column)
                    <flux:cell>{{ $builder->{$column} }}</flux:cell>
                @endforeach

                <flux:cell>
                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>
                </flux:cell>
            </flux:row>
        @endforeach
    </flux:rows>
</flux:table>