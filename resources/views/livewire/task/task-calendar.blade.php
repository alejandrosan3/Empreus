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
                    class="flex flex-col gap-2 px-2 sortable h-full calendarColumn {{ $day['isToday'] ? 'bg-blue-50/50 dark:bg-blue-900/10' : '' }}"
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
                                            <flux:badge color="green" size="sm" title="Floor Area">
                                                {{ $task->floor_area_category }}
                                            </flux:badge>
                                                @if( $task->taskNotes->isNotEmpty() )
                                                    <flux:modal.trigger name="note-{{ $task->id }}">
                                                        <flux:button
                                                            size="xs"
                                                            variant="subtle"
                                                            color="slate"
                                                            icon="chat-bubble-left-right"
                                                            title="Show latest note"
                                                            wire:click="showLatestNote({{ $task->id }})"
                                                        />
                                                    </flux:modal.trigger>

                                                    <flux:modal name="note-{{ $task->id }}" class="p4 min-w-[22rem] min-h-[4rem]">
                                                        {{ $currentNote }}
                                                    </flux:modal>
                                                @endif
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
                                            <dt class="sr-only">Subdivision/Lot</dt>
                                            <dd class="flex items-center gap-1">
                                                <flux:icon name="home" class="w-4 h-4 text-gray-500" />
                                                {{ $task->lot->subdivision }} - {{ $task->lot->lot_number }}
                                            </dd>
                                        </dl>

                                    </flux:text>
                                </div>
                            @endforeach
                                {{-- “Load more” for this day --}}
                                @if($hasMore[$day['date']] ?? false)
                                    <div class="text-center py-2">
                                        <flux:button
                                            size="xs"
                                            wire:click="$dispatch('load-more', [null, '{{ $day['date'] }}'])"
                                        >Load More
                                            <span>
                                                ({{ $howManyMore[$day['date']] }}+)
                                            </span>
                                        </flux:button>
                                    </div>
                                @endif
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
