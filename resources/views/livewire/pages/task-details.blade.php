<div class="p-4">
    <flux:button
    icon="arrow-left"
    variant="ghost"
    onclick="window.history.length > 1 ? history.back() : window.location = '{{ route('lot-progress') }}'">
    Back
</flux:button>

    <flux:heading size="xl" class="mb-4">Name of the content detail page view <flux:badge color="yellow" inset="top bottom">Tag?</flux:badge> </flux:heading>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        <!-- Builder Details -->
        @livewire('partials.card', [
            'items' => [
                ['label' => 'Builder', 'value' => $lot->builder->name, 'level' => 2],
                ['label' => 'Subdivision', 'value' => $lot->subdivision],
                ['label' => 'Lot', 'value' => $lot->lot_number],
            ]
        ])


        <!-- Invoice and pricing -->
        <div>
            <flux:card class="space-y-6 bg-gray-500/10">
                <flux:heading size="lg" level="3">PO: {{ $lot->po ?? 'N/A' }}</flux:heading>
                <flux:heading level="5">Price: ${{ $lot->price ?? '0.00' }}</flux:heading>
                <flux:heading level="5"> Status: <flux:badge variant="solid" color="{{ $lot->statusType->color }}" inset="top bottom">{{ $lot->statusType->name }}</flux:badge> </flux:heading>
            </flux:card>
        </div>

        <!-- Supervisor Section -->
        <div>
            <flux:card class="space-y-6 bg-gray-500/10">
                    <flux:heading size="lg" level="3">Supervisor</flux:heading>
                    <flux:heading level="5">Name: {{ $lot->supervisor->name }}</flux:heading>
                    <flux:heading level="5">Group: {{ $lot->supervisor->group }}</flux:heading>
            </flux:card>
        </div>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <!-- Notes Section -->
        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg" Level="2">Notes</flux:heading>
                <p>
                    Once the data is cleaned and prepared, you can proceed with further analysis and visualization to extract meaningful insights and inform decision-making.
                </p>
            </div>
        </flux:card>

        <!-- Description Info Section -->
        <flux:card class="space-y-6">
            <div>
                <flux:heading size="lg" Level="2">Description</flux:heading>
                <p>
                    Once the data is cleaned and prepared, you can proceed with further analysis and visualization to extract meaningful insights and inform decision-making.
                </p>
            </div>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 gap-4 mt-4">
        <!-- Standard Task Tables -->
        <div>
            @foreach ($tasks as $type => $taskGroup)
            <flux:card class="space-y-6 mb-5">
                <flux:heading size="lg">{{ ucfirst($type) }} Tasks <flux:badge color="lime" inset="top bottom">{{ $type === 'standard' ? 'PO' : 'EPO' }}</flux:badge></flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Builder</flux:table.column>
                        <flux:table.column>Subdivision</flux:table.column>
                        <flux:table.column>Lot</flux:table.column>
                        <flux:table.column>Status</flux:table.column>
                        <flux:table.column>Start Date</flux:table.column>
                        <flux:table.column>Completed Date</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($taskGroup as $task)
                        <flux:table.row class="{{ $task->statusType->name === 'Completed' ? 'opacity-50' : '' }}">
                            <flux:table.cell>{{ $task->lot->builder->name }}</flux:table.cell>
                            <flux:table.cell>{{ $task->lot->subdivision }}</flux:table.cell>
                            <flux:table.cell>{{ $task->lot->lot_number }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge color="{{ $task->statusType->color }}">{{ $task->statusType->name }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $task->started_at }}</flux:table.cell>
                            <flux:table.cell>{{ $task->ended_at }}</flux:table.cell>
                        </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </flux:card>
            @endforeach
        </div>
    </div>
</div>
