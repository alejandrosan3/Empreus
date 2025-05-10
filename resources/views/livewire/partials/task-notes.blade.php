<div>
    <flux:card class="space-y-6 mb-5">
        <flux:heading size="lg">
            Task Notes
            <flux:badge color="purple" inset="top bottom">Notes</flux:badge>
        </flux:heading>
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'task_notes.created_at' " wire:click="sort( 'task_notes.created_at' )">Created At</flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'task_type.name' " wire:click="sort( 'task_type.name' )">Task</flux:table.column>
                <flux:table.column>Note</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($allNotes as $note)
                    <flux:table.row>
                        <flux:table.cell>{{ $note->updated_at->format('Y-m-d') ?? 'N/A' }}</flux:table.cell>
                        <flux:table.cell>{{ $note->task->taskType->name ?? 'N/A' }}</flux:table.cell>
                        <flux:table.cell>{{ $note->description }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center">
                            No notes available.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
