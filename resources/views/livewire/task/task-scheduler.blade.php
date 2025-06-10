<div class="grid grid-cols-1 md:grid-cols-{{ count($statuses) }} gap-4">
    @foreach($statuses as $status)
        <flux:card class="min-h-80">
            <flux:card.header class="text-center font-bold">
                {{ $status->name }}
            </flux:card.header>
            <ul id="column-{{ $status->id }}" class="min-h-32 space-y-2 p-2"
                x-init="initSortable($el, {{ $status->id }})">
                @foreach(($groupedTasks[$status->id] ?? []) as $task)
                    <li class="p-2 bg-white dark:bg-zinc-700 rounded shadow" data-id="{{ $task->id }}">
                        {{ $task->taskType->name }}
                    </li>
                @endforeach
            </ul>
        </flux:card>
    @endforeach
</div>

<script>
    function initSortable(el, statusId) {
        new Sortable(el, {
            group: 'tasks',
            animation: 150,
            onAdd: (evt) => {
                const taskId = evt.item.getAttribute('data-id');
                Livewire.dispatch('task-moved', {taskId: taskId, statusId: statusId});
            }
        });
    }
</script>
