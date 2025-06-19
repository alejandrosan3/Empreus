<div>
    {{-- Columns Container --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 overflow-x-auto pb-4">
        @foreach ($this->getStatuses() as $status => $label)
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
                                {{ count($this->tasks[$status]) }}
                            </flux:badge>
                        </flux:heading>
                    </div>

                    <flux:button variant="subtle" icon="ellipsis-horizontal" size="sm" />
                </div>

                {{-- ======================
                     Task Cards Container
                ======================= --}}
                <div
                    class="flex flex-col gap-2 px-2 sortable h-full"
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
                                <div class="flex gap-2">
                                    @if($task->is_extra)
                                        <flux:badge color="blue" size="sm" title="Extra Purchase Order">EPO</flux:badge>
                                    @endif
                                    <flux:badge color="yellow" size="sm" title="Builder name">
                                        {{ $task->builder_name }}
                                    </flux:badge>
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
                                        <flux:icon name="rectangle-stack" class="w-4 h-4 text-gray-500" />
                                        {{ $task->lot->subdivision }} - {{ $task->lot->lot_number }}
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
                        {{-- Empty column state --}}
                        <div class="text-gray-500 text-sm text-center py-4">
                            No tasks in this status
                        </div>
                    @endforelse
                    @if($hasMore[$status] ?? false)
                        <div class="text-center py-2">
                            <flux:button size="xs" wire:click="loadMore('{{ $status }}')">Load More</flux:button>
                        </div>
                    @endif
                    <div class="h-full min-h-[150px] empty-column" aria-hidden="true"></div>
                </div> {{-- End Task Cards Container --}}
            </div> {{-- End Status Column Wrapper --}}
        @endforeach
    </div>

    {{-- ======================
         Drag & Drop (Sortable)
    ======================= --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const columns = document.querySelectorAll('.sortable');

            columns.forEach(column => {
                new Sortable(column, {
                    group: {
                        name: 'kanban',
                        pull: true,
                        put: true,
                    },
                    animation: 150,
                    draggable: '.cursor-move',
                    onEnd: (event) => {
                        const taskId         = event.item.dataset.id;
                        const newStatus      = event.to.dataset.status;
                        const originalStatus = event.from.dataset.status;

                        // Only trigger Livewire update if status actually changed
                        if (originalStatus !== newStatus) {
                            const component = event.to.closest('[wire\\:id]').__livewire;
                            component.$wire.updateTaskStatus(taskId, newStatus);
                        }

                        if (event.from.querySelectorAll('.cursor-move').length === 0) {
                            const component = event.from.closest('[wire\\:id]').__livewire;
                            component.$wire.loadMore(event.from.dataset.status);
                        }
                    },
                });
            });
        });
    </script>
</div>
