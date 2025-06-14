<div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 overflow-x-auto pb-4">
        @foreach ($this->getStatuses() as $status => $label)
            <div
                class="flex flex-col bg-white border border-gray-200 rounded-2xl shadow-sm p-4"
                id="status-{{ $status }}"
            >

                <div class="px-4 py-4 flex justify-between items-start">
                    <div>
                        <flux:heading>{{ $label }} <flux:badge color="blue" size="sm">{{ count($this->tasks[$status]) }}</flux:badge></flux:heading>
                    </div>
                    <flux:button variant="subtle" icon="ellipsis-horizontal" size="sm" />
                </div>

                <div
                    class="flex flex-col gap-2 px-2 sortable h-full"
                    data-status="{{ $status }}"
                    aria-label="{{ $label }} tasks"
                >
                    @forelse ($tasks[$status] ?? [] as $task)
                    <div
                            wire:key="task-{{ $task->id }}"
                            data-id="{{ $task->id }}"
                            class="bg-white rounded-lg shadow-xs border border-zinc-200 dark:border-white/10 dark:bg-zinc-800 p-3 space-y-2 cursor-move"
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
                            <flux:text class="text-sm text-gray-600">
                                <dl class="space-y-1">
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
                                        Supervisor: {{ Str::title($task->supervisor_name) }}
                                    </dd>


                                    <dt class="sr-only">Last Update</dt>
                                    <dd>Last updated on {{ $task->updated_at->format('M j, Y') }}</dd>
                                </dl>

                            </flux:text>
                        </div>
                    @empty
                        <div class="text-gray-500 text-sm text-center py-4">
                            No tasks in this status
                        </div>
                    @endforelse
                    <div class="h-full min-h-[150px] empty-column" aria-hidden="true"></div>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const columns = document.querySelectorAll('.sortable');

            columns.forEach(column => {
                new Sortable(column, {
                    group: {
                        name: 'kanban',
                        pull: true,
                        put: true
                    },
                    animation: 150,
                    draggable: '.cursor-move',
                    onEnd: (event) => {
                        const taskId = event.item.dataset.id;
                        const newStatus = event.to.dataset.status;
                        const originalStatus = event.from.dataset.status;

                        if (originalStatus !== newStatus) {
                            event.to.closest('[wire\\:id]').__livewire.$wire.updateTaskStatus(taskId, newStatus);
                        }
                    }
                });
            });
        });
    </script>
</div>
