<flux:card class="space-y-6 mb-5">
    <flux:heading size="lg">
        {{ $title }}
        <flux:badge color="{{ $badgeColor }}" inset="top bottom">{{ $badgeLabel }}</flux:badge>
    </flux:heading>
    <flux:table>
        <flux:columns>
            @foreach ($headers as $header)
                <flux:column>{{ $header }}</flux:column>
            @endforeach
        </flux:columns>
        <flux:rows>
            @forelse ($taskGroup as $task)
                <flux:row class="{{ ($task->statusType->name ?? '') === 'Completed' ? 'opacity-50' : '' }}">
                    <flux:cell>{{ $task->taskType->name ?? 'Unknown' }}</flux:cell>
                    <flux:cell>
                        <flux:badge color="{{ $task->statusType->color ?? 'gray' }}">
                            {{ $task->statusType->name ?? 'Unknown' }}
                        </flux:badge>
                    </flux:cell>
                    <flux:cell>{{ $task->scheduled_start_date ?? '-' }}</flux:cell>
                    <flux:cell>{{ $task->started_at ?? '-' }}</flux:cell>
                    <flux:cell>{{ $task->ended_at ?? '-' }}</flux:cell>
                </flux:row>
            @empty
                <flux:row>
                    <flux:cell colspan="{{ count($headers) }}" class="text-center">
                        No tasks available.
                    </flux:cell>
                </flux:row>
            @endforelse
        </flux:rows>
    </flux:table>
</flux:card>
