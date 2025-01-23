<div class="p-4">
    <flux:button 
    icon="arrow-left" 
    variant="ghost" 
    onclick="window.history.length > 1 ? history.back() : window.location = '{{ route('lot-progress') }}'">
    Back
</flux:button>

    <flux:heading size="xl" class="mb-4">Lot Details <flux:badge color="yellow" inset="top bottom">Tag?</flux:badge> </flux:heading>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 gap-4">
        <!-- Builder Details -->
        @livewire('partials.cards', [
            'items' => [
                ['label' => 'Builder', 'value' => $lot->builder->name, 'level' => 2],
                ['label' => 'Subdivision', 'value' => $lot->subdivision],
                ['label' => 'Lot', 'value' => $lot->lot_number],
                ['label' => 'Phase', 'value' => $lot->phase],
                ['label' => 'Account', 'value' => $lot->company_id]
            ]
        ])
    </div>


    <div class="grid grid-cols-1 gap-4 mt-4">
        <!-- Notes Section -->
        @livewire('partials.task-notes', [
            'tasks' => $lot->tasks, 
        ])
        
    </div>

    <div class="grid grid-cols-1 gap-4 mt-4">
        <!-- Standard Task Tables -->
        <div>
            @foreach ($tasks as $type => $taskGroup)
            @livewire('partials.task-table', [
                'title' => ucfirst($type) . ' Tasks',
                'badgeColor' => $type === 'standard' ? 'lime' : 'blue',
                'badgeLabel' => $type === 'standard' ? 'PO' : 'EPO',
                'headers' => ['Builder', 'Subdivision', 'Lot', 'Status', 'Start Date', 'Completed Date'],
                'taskGroup' => $taskGroup,
            ])
            @endforeach
        </div>
    </div>
</div>