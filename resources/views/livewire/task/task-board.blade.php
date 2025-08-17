<div>

    <livewire:teams.manage-teams :key="'manage-teams'" />
    <flux:checkbox.group x-data="bulkAssignCheckboxes()">
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
        {{-- Columns Container --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 overflow-x-auto pb-4">
            @foreach ($statuses as $status => $label)
                {{-- =========================
                     Status Column Wrapper
                ========================== --}}
                <div
                    id="status-{{ $status }}"
                    class="flex flex-col bg-white dark:bg-zinc-900 border border-gray-200 dark:border-white/10 rounded-2xl shadow-sm p-4"
                >
                    {{-- Column Header --}}
                    <div class="px-4 py-4 flex justify-between items-start">
                        <div>
                            <flux:heading>
                                {{ $label }}
                                <flux:badge color="blue" size="sm">
                                    {{ $totalTasksByStatus[$status] ?? 0 }}
                                </flux:badge>
                            </flux:heading>
                        </div>

                        <flux:button variant="subtle" icon="ellipsis-horizontal" size="sm" />
                    </div>

                    {{-- ======================
                         Task Cards Container
                    ======================= --}}
                    <div
                        class="taskBoard flex flex-col gap-2 px-2 sortable h-full"
                        data-status="{{ $status }}"
                        aria-label="{{ $label }} tasks"
                    >
                        {{-- ==== Single Task Card ==== --}}
                        @forelse ($tasks[$status] ?? [] as $task)
                            <div
                                wire:key="task-{{ $task->id }}"
                                data-id="{{ $task->id }}"
                                aria-label="Task {{ $task->id }}"
                                class="bg-white rounded-lg shadow-xs border border-zinc-200 dark:border-white/10 dark:bg-zinc-800 p-3 space-y-2 cursor-move"
                            >
                                {{-- Card header --}}
                                <div class="flex justify-between">
                                    {{-- Badges --}}
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

                                    {{-- “Open in new tab” button --}}
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

                                {{-- Card body --}}
                                <flux:heading>{{ $task->task_type_name }}</flux:heading>

                                <flux:text class="text-sm text-gray-600 dark:text-white/80">
                                    <dl class="space-y-2">
                                        {{-- Subdivision / Lot --}}
                                        <dt class="sr-only">Subdivision/Lot</dt>
                                        <dd class="flex items-center gap-1">
                                            <flux:icon name="home" class="w-4 h-4 text-gray-500" />
                                            {{ $task->lot->subdivision }} - {{ $task->lot->lot_number }}
                                        </dd>

                                        <dd class="flex items-center gap-1">
                                            <flux:icon name="arrows-pointing-out" class="w-4 h-4 text-gray-500" />
                                            {{ $task->lot->house_type }} - {{ $task->lot->stories_count }} floor{{$task->lot->stories_count > 1 ? 's' : '' }}

                                        </dd>

                                        <dt class="sr-only">Scheduled Start</dt>
                                        <dd class="flex items-center gap-1 font-bold">
                                            <flux:icon name="calendar" class="w-4 h-4 text-gray-500" />
                                            {{ $task->scheduled_start_date ?? '-' }}
                                        </dd>
                                    </dl>
                                </flux:text>
                            </div>
                        @empty


                        @endforelse
                        @if($hasMore[$status] ?? false)
                            <div class="text-center py-2">
                                <flux:button size="xs"
                                     wire:click="$dispatch('load-more', ['{{ $status }}'] )">Load More
                                </flux:button>
                            </div>
                        @endif

                    </div> {{-- End Task Cards Container --}}
                </div> {{-- End Status Column Wrapper --}}
            @endforeach
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
