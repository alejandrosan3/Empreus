<div>

    {{-- Calendar Grid --}}
    <div class="flex flex-col bg-white dark:bg-zinc-900 border border-gray-200 dark:border-white/10 rounded-2xl shadow-sm p-4">
        <div class="grid gap-4" style="grid-template-columns: repeat({{ $rangeDays }}, minmax(0,1fr));">
            {{-- Calendar Header --}}
            @foreach($weekDays as $day)
                <div class="text-center p-4 {{ $day['isToday'] ? 'bg-blue-50 dark:bg-blue-900/20 rounded-t-lg border-t-2 border-blue-500' : 'bg-gray-50 dark:bg-zinc-800 rounded-t-lg' }}">
                    <div class="font-semibold {{ $day['isToday'] ? 'text-blue-700 dark:text-blue-400' : '' }}">
                        <flux:badge color="blue" size="sm">
                            {{ count($tasksByDay[$day['date']] ?? []) }}
                        </flux:badge>
                        {{ $day['dayName'] }}
                        
                    </div>
                    <div class="text-lg {{ $day['isToday'] ? 'text-blue-700 dark:text-blue-400' : '' }}">{{ $day['dayNumber'] }}</div>
                </div>
            @endforeach

            {{-- Calendar Content --}}
            @foreach($weekDays as $day)
                <div
                    id="calendarTask" 
                    class="flex flex-col gap-2 px-2 sortable h-full calendarTask-column {{ $day['isToday'] ? 'bg-blue-50/50 dark:bg-blue-900/10' : '' }}"
                    data-date="{{ $day['date'] }}"
                >
                @if(isset($tasksByDay[$day['date']]))
                        @foreach($tasksByDay[$day['date']] as $task)
                            <div
                                wire:key="task-{{ $task->id }}"
                                data-task-id="{{ $task->id }}"
                                class="task-item bg-white rounded-lg shadow-xs border border-zinc-200 dark:border-white/10 dark:bg-zinc-800 p-3 space-y-2 cursor-move"
                                aria-label="Task {{ $task->id }}"
                            >
                                <div class="flex justify-between">
                                    <div class="flex gap-2">
                                        @if($task->is_extra)
                                            <flux:badge color="blue" size="sm" title="Extra Purchase Order">EPO</flux:badge>
                                        @endif
                                        <flux:badge color="yellow" size="sm" title="Builder name">{{ $task->builder_name }}</flux:badge>
                                    </div>
                                    <flux:button
                                        variant="subtle"
                                        size="xs"
                                        icon="arrow-top-right-on-square"
                                        href="{{ route('task-details', ['task_id' => $task->id]) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        title="Open Task details"
                                    />
                                </div>

                                <flux:heading>{{ $task->task_type_name }}</flux:heading>
                                <flux:text class="text-sm text-gray-600 dark:text-white/80">
                                    <dl class="space-y-2">
                                        <dt class="sr-only">Address</dt>
                                        <dd class="flex items-center gap-1">
                                            <flux:icon name="map-pin" class="w-4 h-4 text-gray-500" />
                                            {{ $task->address }}
                                        </dd>

                                        <dt class="sr-only">Subdivision/Lot</dt>
                                        <dd class="flex items-center gap-1">
                                            <flux:icon name="home" class="w-4 h-4 text-gray-500" />
                                            {{ $task->subdivision }} - {{ $task->lot_number }}
                                        </dd>

                                        <dd class="flex items-center gap-1">
                                            <flux:icon name="user" class="w-4 h-4 text-gray-500" />
                                            {{ Str::title($task->supervisor_name) }}
                                        </dd>
                                    </dl>

                                </flux:text>
                            </div>

                        @endforeach
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    @script
        <script>
            console.log("calendar script init");
            (async () => {

                //if (window.__taskCalendarSortableHookAdded) return;
                //window.__taskCalendarSortableHookAdded = true;
                
                /** re‑initialise Sortable on any .task‑column that hasn’t been done yet */
                const initSortableCalendar = () => {

                    console.log('[TaskCalendar] initSortableCalendar fired');
                    // only grab columns we haven’t touched before
                    const columns = document.querySelectorAll('#calendarTask.sortable:not([data-sortable-init])');

                    columns.forEach(column => {
                        column.dataset.sortableInit = '1';   // mark as initialised

                        new Sortable(column, {
                            group: { name: 'calendar-tasks', pull: true, put: true },
                            animation: 150,
                            draggable: '.task-item',
                            ghostClass: 'opacity-50',
                            chosenClass: 'bg-gray-100',

                            onEnd(evt) {
                                const taskId   = evt.item.dataset.taskId;
                                const newDate  = evt.to.dataset.date;
                                const oldDate  = evt.from.dataset.date;

                                console.log("taskid: " + taskid + "newdate: " + newDate + "old date: " + oldDate);
                                // only hit Livewire if the day really changed
                                if (taskId && newDate && oldDate !== newDate) {
                                    const comp = evt.to.closest('[wire\\:id]').__livewire;
                                    $wire.call('taskDateUpdated', id, newDate);
                                }
                            }
                        });
                    });
                };

                // run once after Livewire boots
                document.addEventListener('livewire:init', initSortableCalendar);

                //run after every update
                Livewire.hook('morphed', initSortableCalendar);

                /* optional: run when you dispatch your own 'tasks‑updated' event */
                document.addEventListener('tasks-updated', initSortableCalendar);
            })();
        </script>
    @endscript

</div>
