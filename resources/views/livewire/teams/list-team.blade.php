<div>
    <flux:table :paginate="$this->teams">
        <flux:table.column
            sortable
            :sorted="$sortBy === 'name'"
            :direction="$sortDirection"
            wire:click="sort('name')">
            Name
        </flux:table.column>
        <flux:table.column>Workers</flux:table.column>

        <flux:table.rows>
            @foreach ($this->teams as $team)
                <flux:table.row :key="$team->id">
                    <flux:table.cell>{{ $team->name }}</flux:table.cell>
                    <flux:table.cell>{{ $team->workers_count }}</flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>