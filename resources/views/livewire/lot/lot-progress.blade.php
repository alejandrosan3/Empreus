<div>
    @if (!$selectedLot)

        <flux:table :paginate="$lots">
            <!-- Dynamic Table Header -->
            <flux:table.columns>
                @foreach($columns as $column)
                    @if($column['selected'])
                        @if($column['value'] === 'standard_po')
                            {{-- When the column is "standard_po", we output the dynamic task columns --}}
                            @foreach($taskTypes as $taskType)
                                <flux:table.column>{{ $taskType }}</flux:table.column>
                            @endforeach
                        @else
                            {{-- Output a sortable column if needed --}}
                            <flux:table.column :sortable="$column['sortable']"
                            :sorted="$column['sortable'] ? $sortBy === $column['value'] : false "
                            wire:click="{{ $column['sortable'] ? 'sort(\'' . $column['value'] . '\')' : '' }}"
                            class="bg-white dark:bg-zinc-800 {{ $column['group'] == 'fixed' ? 'sticky' : '' }}"
                            data-fixed-index=" {{ $column['group'] == 'fixed' ? $loop->index : '' }}">
                                {{ $column['label'] }}
                            </flux:table.column>
                        @endif
                    @endif


                @endforeach
                <!-- Static Actions Column -->
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <!-- Table Content -->
            <flux:table.rows>
                @foreach ($lots as $lot)
                    <flux:table.row :key="$lot->id">
                        @foreach($columns as $column)
                            @if($column['selected'])
                                @switch($column['value'])
                                    @case('builder')
                                        <flux:table.cell
                                        class="bg-white dark:bg-zinc-800 {{ $column['group'] == 'fixed' ? 'sticky' : '' }}"
                                        data-fixed-index=" {{ $column['group'] == 'fixed' ? $loop->index : '' }}" >
                                            {{ $lot->builder->name }}
                                        </flux:table.cell>
                                        @break

                                    @case('subdivision')
                                        <flux:table.cell
                                        class="bg-white dark:bg-zinc-800 {{ $column['group'] == 'fixed' ? 'sticky' : '' }}"
                                        data-fixed-index=" {{ $column['group'] == 'fixed' ? $loop->index : '' }}">
                                            {{ $lot->subdivision . ($lot->phase ? " ({$lot->phase})" : "") }}
                                        </flux:table.cell>
                                        @break

                                    @case('lot_number')
                                        <flux:table.cell
                                        class="bg-white dark:bg-zinc-800 {{ $column['group'] == 'fixed' ? 'sticky' : '' }}"
                                        data-fixed-index=" {{ $column['group'] == 'fixed' ? $loop->index : '' }}">
                                            {{ $lot->lot_number }}
                                        </flux:table.cell>
                                        @break

                                    @case('stories')
                                        <flux:table.cell
                                        class="bg-white dark:bg-zinc-800 {{ $column['group'] == 'fixed' ? 'sticky' : '' }}"
                                        data-fixed-index=" {{ $column['group'] == 'fixed' ? $loop->index : '' }}">
                                            {{ $lot->num_of_floors ?? '-' }}
                                        </flux:table.cell>
                                        @break

                                    @case('last_completed_date')
                                        <flux:table.cell
                                        class="bg-white dark:bg-zinc-800 {{ $column['group'] == 'fixed' ? 'sticky' : '' }}"
                                        data-fixed-index=" {{ $column['group'] == 'fixed' ? $loop->index : '' }}">
                                            @php
                                                $lastCompleted = $lot->getLastCompletedTask();
                                            @endphp
                                            @if($lastCompleted)
                                                {{ $lastCompleted->taskType->name }} ({{ $lastCompleted->ended_at }})
                                            @else
                                                <em>No tasks completed yet</em>
                                            @endif
                                        </flux:table.cell>
                                        @break

                                    @case('epo')
                                        <flux:table.cell
                                        class="bg-white dark:bg-zinc-800{{ $column['group'] == 'fixed' ? 'sticky' : '' }}"
                                        data-fixed-index=" {{ $column['group'] == 'fixed' ? $loop->index : '' }}">
                                            @php
                                                $epoCount = $this->epoCount($lot);
                                            @endphp
                                            <flux:heading class="flex items-center gap-2">
                                                {{ $epoCount['completed'] }}/{{ $epoCount['total'] }}
                                                @if ($epoCount['total'])
                                                    <flux:tooltip toggleable>
                                                        <flux:button icon="information-circle" size="sm" variant="ghost" />
                                                        <flux:tooltip.content class="max-w-[20rem] space-y-2">
                                                            <div class="flex flex-col space-y-2">
                                                                @foreach($lot->tasks->where('is_extra', 1) as $task)
                                                                    <div class="flex items-center space-x-2">
                                                                        @if($task->ended_at)
                                                                            @php $completed = "Completed= $task->ended_at" ; @endphp
                                                                            <flux:icon.check variant="micro" class="text-green-500 dark:text-green-300" />
                                                                        @else
                                                                            @php $completed = ""; @endphp
                                                                            <flux:icon.clock variant="micro" class="text-amber-500 dark:text-amber-300" />
                                                                        @endif
                                                                        <span>{{ $task->taskType->name }}. {{ $completed }}</span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </flux:tooltip.content>
                                                    </flux:tooltip>
                                                @endif
                                            </flux:heading>
                                        </flux:table.cell>
                                        @break

                                    @case('standard_po')
                                        @foreach ($taskTypes as $taskType)
                                            <flux:table.cell
                                            class="bg-white dark:bg-zinc-800{{ $column['group'] == 'fixed' ? 'sticky' : '' }}"
                                            data-fixed-index=" {{ $column['group'] == 'fixed' ? $loop->index : '' }}">
                                                @if($task = $lot->getTaskByTypeName($taskType))
                                                    @if($task->status->isCompleted())
                                                        <flux:tooltip content="Completed on: {{ $task->ended_at }}">
                                                            <flux:checkbox checked disabled />
                                                        </flux:tooltip>
                                                    @else
                                                        <flux:checkbox disabled />
                                                    @endif
                                                @else
                                                    -
                                                @endif
                                            </flux:table.cell>
                                        @endforeach
                                        @break

                                @endswitch
                            @endif


                        @endforeach

                        <!-- Static Actions Cell -->
                        <flux:table.cell>
                            <flux:dropdown>
                                <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                                <flux:menu>
                                    <flux:menu.item icon="clipboard-document-check" wire:click="loadDetails({{ $lot->id }})">
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
        <!-- Render the ContentDetails component dynamically -->
        <div>
            @livewire('pages.lot-details', ['lot' => $selectedLot])
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
