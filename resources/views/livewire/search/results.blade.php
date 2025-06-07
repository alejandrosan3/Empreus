<flux:section class="p-6">
    <flux:heading size="lg">Search Results</flux:heading>

    @if($query === '')
        <p class="mt-4">Please enter a search term.</p>
    @else
        @if($this->results->isEmpty())
            <p class="mt-4">No results found.</p>
        @else
            <flux:table>
                <flux:table.column>Lot #</flux:table.column>
                <flux:table.column>Address</flux:table.column>
                <flux:table.column>Builder</flux:table.column>
                <flux:table.column>Task Type</flux:table.column>
                <flux:table.column>Status</flux:table.column>

                <flux:table.rows>
                    @foreach($this->results as $task)
                        <flux:table.row :key="$task->id">
                            <flux:table.cell>{{ $task->lot_number }}</flux:table.cell>
                            <flux:table.cell>{{ $task->address }}</flux:table.cell>
                            <flux:table.cell>{{ $task->builder }}</flux:table.cell>
                            <flux:table.cell>{{ $task->task_type }}</flux:table.cell>
                            <flux:table.cell>{{ $task->status }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    @endif
</flux:section>
