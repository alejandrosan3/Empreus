<div class="p-4">
    <flux:button
    icon="arrow-left"
    variant="ghost"
    onclick="window.history.length > 1 ? history.back() : window.location = '{{ route('lot-progress') }}'">
    Back
</flux:button>
    <flux:heading size="xl" class="mb-4">Lot Details <flux:badge color="yellow" inset="top bottom">{{ $lot->company->name }}</flux:badge> </flux:heading>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 gap-4">
        <!-- Builder Details -->
        @livewire('partials.cards', [
            'items' => [
                ['label' => 'Builder', 'value' => $lot->builder->name, 'level' => 2],
                ['label' => 'Subdivision', 'value' => $lot->subdivision],
            ]
        ])

        @livewire('partials.cards', [
            'items' => [
                ['label' => 'Supervisor', 'value' => $lot->supervisor->name],
                ['label' => 'Lot', 'value' => $lot->lot_number],
                ['label' => 'Phase', 'value' => $lot->phase]
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
                'headers' => [
                    ['label'=>'Name', 'column'=>'task_type.name'],
                    ['label'=>'Status', 'column'=> 'status_type.name'],
                    ['label'=>'Scheduled For', 'column'=>'scheduled_start_date'],
                    ['label'=>'Start Date', 'column'=>'started_at'],
                    ['label'=>'Completed Date', 'column'=>'ended_at']
                ],
                'taskGroup' => $taskGroup,
            ])
            @endforeach
        </div>
    </div>
</div>
