<flux:card class="space-y-6 mb-5">
    <flux:heading size="lg">
        {{ $title }}
        <flux:badge color="{{ $badgeColor }}" inset="top bottom">{{ $badgeLabel }}</flux:badge>
    </flux:heading>
    <flux:table>
        <flux:table.columns>
            @foreach ($headers as $header)
                <flux:table.column sortable :sorted="$sortBy === $header['column']" wire:click="sort( '{{ $header['column'] }}' )" >{{ $header['label'] }}</flux:table.column>
            @endforeach
            <flux:table.column>Workers</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($sortedGroup as $task)
                <flux:table.row class="{{ ($task->statusType->name ?? '') === 'Completed' ? 'opacity-50' : '' }}">
                    <flux:table.cell>{{ $task->taskType->name ?? 'Unknown' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge color="{{ $task->statusType->color ?? 'gray' }}">
                            {{ $task->statusType->name ?? 'Unknown' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $task->scheduled_start_date ?? '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $task->started_at ?? '-' }}</flux:table.cell>
                    <flux:table.cell>{{ $task->ended_at ?? '-' }}</flux:table.cell>
                    <flux:table.cell> <livewire:layout.avatar-stack :users="$task->workers" :avSize="'sm'" :key="'row-stack-'.$task->id" /> </flux:table.cell>
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                            <flux:menu>
                                <flux:menu.item icon="clipboard-document-check" href="{{ route('task-details', ['task_id' => $task->id]) }}" wire:navigate>
                                    Details
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="{{ count($headers) + 2 }}" class="text-center">
                        No tasks available.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</flux:card>
