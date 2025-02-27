<div>
    @if (!$selectedTask)
        <flux:table :paginate="$tasks">
            <!-- Table Header -->
            <flux:table.columns>
                @foreach($columns as $column)
                    @if($column['selected'])
                        <flux:table.column
                            :sortable="$column['sortable']"
                            :sorted="$column['sortable'] ? $sortBy === $column['value'] : false"
                            wire:click="{{ $column['sortable'] ? 'sort(\'' . $column['value'] . '\')' : '' }}"
                            class="bg-white dark:bg-zinc-800 {{ $column['group'] == 'fixed' ? 'sticky' : '' }}"
                            data-fixed-index=" {{ $column['group'] == 'fixed' ? $loop->index : '' }}"
                        >
                            {{ $column['label'] }}
                        </flux:table.column>
                    @endif
                @endforeach
                <!-- Actions Column -->
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <!-- Table Rows -->
            <flux:table.rows>
                @foreach($tasks as $task)
                    <flux:table.row :key="$task->id">
                        @foreach($columns as $column)
                            @if($column['selected'])
                                <flux:table.cell
                                    class="bg-white dark:bg-zinc-800 {{ $column['group'] == 'fixed' ? 'sticky' : '' }}"
                                    data-fixed-index=" {{ $column['group'] == 'fixed' ? $loop->index : '' }}">
                                    @switch($column['value'])
                                        @case('builder')
                                            {{ $task->lot->builder->name }}
                                            @break

                                        @case('subdivision')
                                            {{ $task->lot->subdivision }}
                                            @break

                                        @case('address')
                                            {{ $task->lot->address ?? '-' }}
                                            @break

                                        @case('lot_number')
                                            {{ $task->lot->lot_number }}
                                            @break

                                        @case('task_type')
                                            {{ $task->taskType->name }}
                                            @break

                                        @case('status')
                                            @php
                                                $status = $task->statusType->name; // e.g. 'Completed', 'In Progress', etc.
                                                // Use color-coded classes to match your screenshot style
                                                $statusColor = match($status) {
                                                    'Completed'   => 'bg-green-100 text-green-700',
                                                    'In Progress' => 'bg-orange-100 text-orange-700',
                                                    'Not Started' => 'bg-gray-100 text-gray-700',
                                                    default       => 'bg-gray-100 text-gray-700',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-sm font-medium {{ $task->statusType->color }}">
                                                {{ $task->statusType->name }}
                                            </span>
                                            @break

                                        @case('num_of_floors')
                                            {{ $task->lot->num_of_floors }}
                                            @break

                                        @case('notes')
                                            {{ $task->taskNote->description ?? "-" }}
                                            @break

                                        @case('floor_area')
                                            {{ $task->lot->floor_area ?? "-" }}
                                            @break

                                        @case('supervisor')
                                            {{ $task->lot->supervisor->name ?? "-" }}
                                            @break

                                        @case('is_extra')
                                            {{ $task->{$column['value']} == 1 ? "Standard" : "EPO" }}
                                            @break

                                        @default
                                            {{-- Default: just output the column value directly --}}
                                            {{ $task->{$column['value']} ?? '-' }}

                                    @endswitch
                                </flux:table.cell>
                            @endif
                        @endforeach

                        <!-- Actions Cell -->
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                                <flux:menu>
                                    <flux:menu.item
                                        icon="clipboard-document-check"
                                        wire:click="showTaskDetails({{ $task->id }})"
                                    >
                                        Details
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

    @else
        <!-- If a task is selected, show its details. -->
        <div>
            @livewire('task.task-details', ['task' => $selectedTask])
        </div>
    @endif
</div>


<script>
    let ticking = false;

    function onScroll() {
        if (!ticking) {
            window.requestAnimationFrame(() => {
            updateStickyOffsets();
            ticking = false;
            });
            ticking = true;
        }
    }

    function updateStickyOffsets() {
        let offset = 0;
        const offsets = [];

        // Get all fixed header columns (assuming they have both data-flux-column and sticky classes)
        const headerFixedCols = document.querySelectorAll('[data-flux-column].sticky');

        headerFixedCols.forEach(col => {
            col.style.left = offset + 'px';
            offsets.push(offset);
            offset += col.offsetWidth;
        });

        // Update each row's fixed cells (assuming they have both data-flux-cell and sticky classes)
        const rows = document.querySelectorAll('[data-flux-row]');
        rows.forEach(row => {
            let cellIndex = 0;
            const fixedCells = row.querySelectorAll('[data-flux-cell].sticky');
            fixedCells.forEach(cell => {
            if (offsets[cellIndex] !== undefined) {
                cell.style.left = offsets[cellIndex] + 'px';
                cell.style.position = 'sticky';
                cell.style.top = '0px';
            }
            cellIndex++;
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const tableContainer = document.querySelector('.overflow-x-auto');
        if (tableContainer) {
            tableContainer.addEventListener('scroll', onScroll);
        }
    });
    window.addEventListener('resize', updateStickyOffsets);


</script>
