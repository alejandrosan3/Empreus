<div>
    @if (!$selectedTask)

        {{-- Modal for hybrid inline editing behavior --}}
        <livewire:partials.editable-modal />
        <flux:checkbox.group wire:model.change="checkedTasks">
            <flux:table :paginate="$tasks">
                <!-- Table Header -->
                <flux:table.columns>
                    <!-- checkbox "Select-All" Column -->
                    <flux:table.column class="sticky left-0 z-20 bg-white dark:bg-zinc-800" >
                        <flux:checkbox.all />
                    </flux:table.column>
                    <!-- Fixed Columns -->
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
                        <flux:table.cell class="sticky left-0 z-20 bg-white dark:bg-zinc-800">
                            <flux:checkbox :value="$task->id" checked="{{ in_array($task->id, $checkedTasks) }}" />
                        </flux:table.cell>
                            @foreach($columns as $column)
                                @if($column['selected'])
                                    @php
                                        // default to editing the Task or Lot record itself
                                        $fieldId = $task->id;

                                        switch($column['value']) {
                                            case 'builder':
                                                $fieldId        = $task->lot->id;
                                                $display        = $task->lot->builder->name;
                                                $rawValue       = $task->lot->builder_id;
                                                $fieldType      = 'select';
                                                $modelClass     = \App\Models\Lot::class;
                                                $modelAttribute = 'builder_id';
                                                break;

                                            case 'subdivision':
                                                $fieldId        = $task->lot->id;
                                                $display        = $task->lot->subdivision;
                                                $rawValue       = $display;
                                                $fieldType      = 'select';
                                                $modelClass     = \App\Models\Lot::class;
                                                $modelAttribute = 'subdivision';
                                                break;

                                            case 'address':
                                                $fieldId        = $task->lot->id;
                                                $display        = $task->lot->address ?? '-';
                                                $rawValue       = $display;
                                                $fieldType      = 'input';
                                                $modelClass     = \App\Models\Lot::class;
                                                $modelAttribute = 'address';
                                                break;

                                            case 'lot_number':
                                                $fieldId        = $task->lot->id;
                                                $display        = $task->lot->lot_number;
                                                $rawValue       = $display;
                                                $fieldType      = 'input';
                                                $modelClass     = \App\Models\Lot::class;
                                                $modelAttribute = 'lot_number';
                                                break;

                                            case 'task_type':
                                                $display        = $task->taskType->name;
                                                $rawValue       = $task->taskType->id;
                                                $fieldType      = 'select';
                                                $modelClass     = \App\Models\Task::class;
                                                $modelAttribute = 'task_type_id';
                                                break;

                                            case 'status':
                                                $display        = $task->statusType->name;
                                                $rawValue       = $task->statusType->id;
                                                $fieldType      = 'select';
                                                $modelClass     = \App\Models\Task::class;
                                                $modelAttribute = 'status_type_id';
                                                break;

                                            case 'num_of_floors':
                                                $fieldId        = $task->lot->id;
                                                $display        = $task->lot->num_of_floors;
                                                $rawValue       = $display;
                                                $fieldType      = 'input';
                                                $modelClass     = \App\Models\Lot::class;
                                                $modelAttribute = 'num_of_floors';
                                                break;

                                            case 'floor_area':
                                                $fieldId        = $task->lot->id;
                                                $display        = $task->lot->floor_area ?? '-';
                                                $rawValue       = $display;
                                                $fieldType      = 'input';
                                                $modelClass     = \App\Models\Lot::class;
                                                $modelAttribute = 'floor_area';
                                                break;

                                            case 'supervisor':
                                                $fieldId        = $task->lot->id;
                                                $display        = $task->lot->supervisor->name ?? '-';
                                                $rawValue       = $task->lot->supervisor->id ?? '';
                                                $fieldType      = 'select';
                                                $modelClass     = \App\Models\Lot::class;
                                                $modelAttribute = 'supervisor_id';
                                                break;

                                            case 'is_extra':
                                                $display        = $task->is_extra ? 'EPO' : 'Standard';
                                                $fieldType      = 'select';
                                                $rawValue       = $task->is_extra;
                                                $modelClass     = \App\Models\Task::class;
                                                $modelAttribute = 'is_extra';
                                                break;

                                            case 'notes':
                                                // Edit the most recent note
                                                $display        = $task->taskNotes->sortByDesc('created_at')->first()->description ?? '-';
                                                $rawValue       = $display;
                                                $fieldType      = 'input';
                                                $modelClass     = \App\Models\TaskNote::class;
                                                $modelAttribute = 'description';
                                                break;
                                            
                                            case 'scheduled_start_date':
                                                $display        = $task->scheduled_start_date;
                                                $rawValue       = $display;
                                                $fieldType      = 'date';
                                                $optionKey      = '';
                                                $modelClass     = \App\Models\Task::class;
                                                $modelAttribute = 'scheduled_start_date';
                                                break;

                                            case 'ended_at':
                                                $display        = $task->ended_at;
                                                $rawValue       = $display;
                                                $fieldType      = 'date';
                                                $optionKey      = '';
                                                $modelClass     = \App\Models\Task::class;
                                                $modelAttribute = 'ended_at';
                                                break;

                                            default:
                                                $display        = $task->{$column['value']} ?? '-';
                                                $rawValue       = $display;
                                                $fieldType      = 'input';
                                                $optionKey      = '';
                                                $modelClass     = \App\Models\Task::class;
                                                $modelAttribute = $column['value'];
                                        }
                                        
                                        $map = [
                                            'builder'    => 'builders',
                                            'task_type'  => 'taskTypes',
                                            'status'     => 'statusTypes',
                                            'supervisor' => 'supervisors',
                                            'subdivision'=> 'subdivisions',
                                            'is_extra'    => 'is_extra',
                                        ];
                                        $optionKey = $map[$column['value']] ?? '';


                                    @endphp

                                    <flux:table.cell
                                        wire:key="cell-{{ $fieldId }}-{{ $column['value'] }}"
                                        x-data
                                        x-on:dblclick="
                                            $dispatch('editField', {
                                            fieldId: {{ $fieldId }},
                                            currentValue: '{{ $rawValue }}',
                                            fieldType: '{{ $fieldType }}',
                                            optionKey: '{{ $optionKey }}',
                                            modelClass: '{{ addslashes($modelClass) }}',
                                            modelAttribute: '{{ $modelAttribute }}',
                                            editable: {{ $column['editable'] ? 'true' : 'false' }}
                                            })"
                                        class="bg-white dark:bg-zinc-800 {{ $column['group']=='fixed'?'sticky':'' }}"
                                        data-fixed-index="{{ $column['group']=='fixed'? $loop->index : '' }}"
                                    >
                                        
                                        @if ($column['value'] === 'status')
                                            <flux:badge color="{{ $task->statusType->color }}"> {{ $display }} </flux:badge>
                                        @else
                                            {{ $display }}
                                        @endif


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
                                            wire:click="loadDetails({{ $task->id }})"
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
        </flux:checkbox.group>

    @else
        <!-- If a task is selected, show its details. -->
        <div>
            @livewire('pages.task-details', ['task' => $selectedTask])
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
