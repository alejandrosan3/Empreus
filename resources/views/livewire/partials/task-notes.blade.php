<div>
    <flux:card class="space-y-6 mb-5">
        <flux:heading size="lg">
            Task Notes
            <flux:badge color="purple" inset="top bottom">Notes</flux:badge>
        </flux:heading>
        <flux:table>
            <flux:columns>
                <flux:column sortable :sorted="$sortBy === 'task_notes.created_at' " wire:click="sort( 'task_notes.created_at' )">Created At</flux:column>
                <flux:column sortable :sorted="$sortBy === 'task_type.name' " wire:click="sort( 'task_type.name' )">Task</flux:column>
                <flux:column>Note</flux:column>
            </flux:columns>
            <flux:rows>
                @forelse ($allNotes as $note)
                    <flux:row>
                        <flux:cell>{{ $note->created_at->format('Y-m-d') ?? 'N/A' }}</flux:cell>
                        <flux:cell>{{ $note->task->taskType->name ?? 'N/A' }}</flux:cell>
                        <flux:cell>{{ $note->description }}</flux:cell>
                    </flux:row>
                @empty
                    <flux:row>
                        <flux:cell colspan="4" class="text-center">
                            No notes available.
                        </flux:cell>
                    </flux:row>
                @endforelse
            </flux:rows>
        </flux:table>
    </flux:card>
</div>
