<div class="p-4">
    <flux:button
    icon="arrow-left"
    variant="ghost"
    wire:click="goBack">
    Back
</flux:button>
    <flux:heading size="xl" class="mb-4">Task Detail <flux:badge color="yellow" inset="top bottom">{{ $task->lot->company->name }}</flux:badge> </flux:heading>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        <!-- Builder Details -->
        @livewire('partials.cards', [
            'items' => [
                ['label' => 'Builder', 'value' => $task->lot->builder?->name ?? 'Missing Builder\'s name', 'level' => 2],
                ['label' => 'Subdivision', 'value' => $task->lot?->subdivision ?? 'Missing Subdivision'],
                ['label' => 'Lot', 'value' => $task->lot?->lot_number ?? 'Lot Number Missing'],
                ['label' => 'Phase', 'value' => $task->lot?->phase ?? '-'],
            ]
        ])

        @livewire('partials.cards', [
            'items' => [
                ['label' => 'Name', 'value' => $task->taskType?->name ?? 'Missing Task Name'],
                ['label' => 'Order Number', 'value' => $task?->order_number ?? '-'],
                ['label' => 'price', 'value' => $task?->price ?? '-'],
                ['label' => 'Order Type', 'value' => $task->is_extra ? "EPO" : "PO"],
            ]
        ])

        @livewire('partials.cards', [
            'items' => [
                ['label' => 'Supervisor', 'value' => $task->lot->supervisor?->name ?? 'N/A'],
                ['label' => 'Scheduled Start Date', 'value' => $task?->scheduled_start_date ?? "Unscheduled"],
                ['label' => 'Scheduled End Date', 'value' => $task?->scheduled_end_date ?? "Unscheduled"],
                ['label' => 'Assigned Group', 'value' => "Coming Soon"],
            ]
        ])
    </div>

    <div class="grid grid-cols-1 gap-4 mt-4">
        <!-- Notes Section -->

        @livewire('partials.task-notes', [
            'tasks' => $task,
        ])

    </div>

    <div class="grid grid-cols-1 gap-4 mt-4">
        <!-- Task Tables -->
        <div>
            @foreach ($taskTypes as $type => $taskGroup)
            @livewire('partials.task-table', [
                'title' => "Related ". ucfirst($type) . ' Tasks',
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
