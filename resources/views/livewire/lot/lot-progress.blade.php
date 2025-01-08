<flux:table :paginate="$lots">
    <flux:columns>
        <flux:column sortable :sorted="$sortBy === 'builder'" wire:click="sort('builder')">Builder</flux:column>
        <flux:column sortable :sorted="$sortBy === 'subdivision'" wire:click="sort('subdivision')">Subdivision</flux:column>
        <flux:column sortable :sorted="$sortBy === 'lot'" wire:click="sort('lot_number')">Lot</flux:column>
        @foreach ($taskTypes as $taskType)
            <flux:column>{{ $taskType }}</flux:column>
        @endforeach
        <flux:column>Floor</flux:column>
        <flux:column>EPOs</flux:column>
        <flux:column sortable :sorted="$sortBy === 'status'" wire:click="sort('status')">Progress</flux:column>
        <flux:column>Actions</flux:column>
    </flux:columns>

    <flux:rows>
        @foreach ($lots as $lot)
            <flux:row :key="$lot->id">
                <flux:cell>{{ $lot->builder->name }}</flux:cell>
                <flux:cell>{{ $lot->subdivision }}</flux:cell>
                <flux:cell>{{ $lot->lot_number }}</flux:cell>
                @foreach ($taskTypes as $taskType)
                    @if($task = $lot->getTaskByTypeName($taskType))
                        <flux:cell>
                            @if($task->status->isCompleted())
                               <flux:checkbox checked disabled />
                            @else
                                <flux:checkbox disabled />
                            @endif
                        </flux:cell>
                    @else
                        <flux:cell> - </flux:cell>
                    @endif
                @endforeach
                <flux:cell>{{ $lot->num_of_floors }}</flux:cell>
                <flux:cell> - </flux:cell>
                <flux:cell>
                    <flux:badge :color="$lot->status->color" variant="pill" size="lg">
                        {{ $lot->status->name }}
                    </flux:badge>
                </flux:cell>
                <flux:cell>
                    <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                </flux:cell>
            </flux:row>
        @endforeach
    </flux:rows>
</flux:table>
