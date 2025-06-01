<div>
    <flux:table :paginate="$this->subdivisions">

        {{-- column headers --}}
        <flux:table.column
            sortable
            :sorted="$sortBy === 'subdivision'"
            :direction="$sortDirection"
            wire:click="sort('subdivision')">
            Subdivision
        </flux:table.column>


        <flux:table.column>Actions</flux:table.column>

        {{-- rows --}}
        <flux:table.rows>
            @foreach ($this->subdivisions as $row)
                <flux:table.row :key="$row->subdivision">
                    <flux:table.cell>{{ $row->subdivision }}</flux:table.cell>

                    {{-- <flux:table.cell>{{ $row->lots_count }}</flux:table.cell> --}}

                    <flux:table.cell>
                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"/>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>

    </flux:table>
</div>
