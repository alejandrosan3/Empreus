<div>
    <livewire:teams.manage-teams :key="'manage-teams'" />
    <flux:checkbox.group x-data="bulkAssignCheckboxes()">
        {{-- Bulk Assign Button --}}
        <div class="h-8 flex items-center gap-2 mb-4">
            <div x-show="selectedIds.length > 0" x-transition
                 class="transition-opacity duration-300 opacity-0"
                 :class="{ 'opacity-100': selectedIds.length > 0 }"
            >
                <flux:button @click="$wire.set('checkedTasks', selectedIds); $wire.startBulkAssign()" size="sm">
                    Assign Workers
                </flux:button>
            </div>
        </div>
        {{-- Calendar Grid --}}
        <div class="flex flex-col bg-white dark:bg-zinc-900 border border-gray-200 dark:border-white/10 rounded-2xl shadow-sm p-4">
            <div class="grid gap-4" style="grid-template-columns: repeat({{ $rangeDays }}, minmax(0,1fr));">
                {{-- Calendar Header --}}
                @foreach($weekDays as $day)
                    <div class="text-center p-4 {{ $day['isToday'] ? 'bg-blue-50 dark:bg-blue-900/20 rounded-t-lg border-t-2 border-blue-500' : 'bg-gray-50 dark:bg-zinc-800 rounded-t-lg' }}">
                        <div class="font-semibold {{ $day['isToday'] ? 'text-blue-700 dark:text-blue-400' : '' }}">
                            <flux:badge color="blue" size="sm">
                                {{ $totalTasksByDay[$day['date']] ?? 0 }}
                            </flux:badge>
                            {{ $day['dayName'] }}

                        </div>
                        <div class="text-lg {{ $day['isToday'] ? 'text-blue-700 dark:text-blue-400' : '' }}">{{ $day['dayNumber'] }}</div>
                    </div>
                @endforeach

                {{-- Calendar Content --}}
                @foreach($weekDays as $day)
                    <div
                        class="grid auto-rows-max gap-2 px-2 sortable h-full calendarColumn {{ $day['isToday'] ? 'bg-blue-50/50 dark:bg-blue-900/10' : '' }}"
                        data-date="{{ $day['date'] }}"
                        style="
                            @if($rangeDays == 1)
                                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                            @elseif($rangeDays == 2)
                                grid-template-columns: repeat(2, minmax(0, 1fr));
                            @else
                                grid-template-columns: repeat(1, minmax(0, 1fr));
                            @endif
                        "
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
                                            <div class="flex items-start gap-2">
                                                <flux:checkbox
                                                    x-data="{ id: {{ $task->id }} }"
                                                    value="{{ $task->id }}"
                                                    x-bind:checked="selectedIds.includes(id)"
                                                    @change="e => {
                                                  if (e.target.checked) selectedIds.push(id);
                                                  else selectedIds = selectedIds.filter(i => i !== id);
                                              }"
                                                />
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

                                                <dd class="flex items-center gap-1">
                                                    <flux:icon name="arrows-pointing-out" class="w-4 h-4 text-gray-500" />
                                                    {{ $task->lot->house_type }} - {{ $task->lot->stories_count }} floor{{$task->lot->stories_count > 1 ? 's' : '' }}
                                                </dd>
                                            </dl>

                                        </flux:text>
                                        {{-- Tags footer: open global modal --}}
                                        <div class="w-full mt-2 pt-2 border-t border-zinc-200 dark:border-white/10 cursor-pointer select-none"
                                             role="button"
                                             aria-label="Edit tags for task {{ $task->id }}"
                                             onclick="Livewire.dispatchTo('task.edit-task-tags-modal', 'open-edit-tags', { taskId: {{ $task->id }} })"
                                        >

                                        <div class="flex flex-wrap gap-1 w-full">
                                                @if($task->tags && $task->tags->isNotEmpty())
                                                    @foreach($task->tags as $tag)
                                                        @php
                                                            $time = $tag->pivot?->time_of_day
                                                                ? \Carbon\Carbon::createFromFormat('H:i:s', $tag->pivot->time_of_day)->format('g:i A')
                                                                : null;
                                                            $label = $time ? ($tag->name . ' @ ' . $time) : $tag->name;
                                                        @endphp
                                                        <span
                                                            class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium border border-zinc-200 dark:border-white/10"
                                                            @if(!empty($tag->color))
                                                                style="background-color: {{ $tag->color }}22; color: {{ $tag->color }}"
                                                            @endif
                                                            title="{{ $label }}"
                                                        >
                                                        {{ $label }}
                                                    </span>
                                                    @endforeach
                                                @else
                                                    <span
                                                        class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium text-zinc-600 bg-zinc-100 border border-zinc-200 dark:text-zinc-300 dark:bg-zinc-700/50 dark:border-white/10"
                                                        title="No tags"
                                                    >
                                                    No tags
                                                </span>
                                                @endif
                                            </div>
                                        </div>

                                    </div>
                                @endforeach
                                    {{-- “Load more” for this day --}}
                                    @if($hasMore[$day['date']] ?? false)
                                        <div class="text-center py-2">
                                            <flux:button
                                                size="xs"
                                                wire:click="$dispatch('load-more', [null, '{{ $day['date'] }}'])"
                                            >Load More
                                            </flux:button>
                                        </div>
                                    @endif
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </flux:checkbox.group>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('bulkAssignCheckboxes', () => ({
                selectedIds: [],
            }));
        });
    </script>
</div>
