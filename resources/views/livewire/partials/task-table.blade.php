<flux:card class="space-y-6 mb-5">
    <flux:heading size="lg">
        {{ $title }}
        <flux:badge color="{{ $badgeColor }}" inset="top bottom">{{ $badgeLabel }}</flux:badge>
    </flux:heading>
    <flux:table>
        <flux:table.columns>
            @foreach ($headers as $header)
                <flux:table.column @if(!empty($header['column'])) sortable :sorted="$sortBy === $header['column']" wire:click="sort('{{ $header['column'] }}')" @endif>
                    {{ $header['label'] }}
                </flux:table.column>
            @endforeach
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
                    <flux:table.cell>
                        <x-user-badges :users="$task->workers" />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="{{ count($headers) }}" class="text-center">
                        No tasks available.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</flux:card>
