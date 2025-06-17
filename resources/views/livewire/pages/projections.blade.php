<div>
    <flux:card class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:date-picker label="Start" with-inputs wire:model="startDate" />
            <flux:date-picker label="End" with-inputs wire:model="endDate" />
        </div>
    </flux:card>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Builder</flux:table.column>
            <flux:table.column>Lot</flux:table.column>
            <flux:table.column>Task</flux:table.column>
            <flux:table.column>Start</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach($tasks as $task)
                <flux:table.row wire:key="task-{{ $task->id }}">
                    <flux:table.cell>{{ $task->lot->builder->name ?? '' }}</flux:table.cell>
                    <flux:table.cell>{{ $task->lot->lot_number }}</flux:table.cell>
                    <flux:table.cell>{{ $task->taskType->name }}</flux:table.cell>
                    <flux:table.cell>{{ $task->scheduled_start_date }}</flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
