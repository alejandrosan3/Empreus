<div>
    <flux:card class="mb-4">
        <flux:heading size="lg">Projections For Next Week</flux:heading>
        <flux:date-picker mode="range" with-inputs wire:model.live="range" />
    </flux:card>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Builder</flux:table.column>
            <flux:table.column>Lot</flux:table.column>
            <flux:table.column>Task</flux:table.column>
            <flux:table.column>Start</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach($this->tasks as $task)
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
