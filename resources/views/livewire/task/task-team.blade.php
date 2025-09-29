<div class="space-y-6">
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
        {{-- No Team bucket always first --}}
        <div class="flex flex-col md:flex-row gap-4 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-white/10 rounded-2xl shadow-sm p-4">
            <div class="w-full md:w-64">
                <h3 class="font-semibold mb-2">No Team</h3>
                <p class="text-sm text-gray-500">Tasks not assigned to any team</p>
            </div>

            <div class="flex-1">
                <div
                    class="teamTasks sortable flex flex-wrap gap-4"
                    id="team-none"
                    data-team-id="none"
                >
                    @foreach (($tasksByTeam['none'] ?? []) as $task)
                        <div
                            wire:key="task-{{ $task['id'] }}"
                            class="task-item bg-white rounded-lg shadow-xs border border-zinc-200 dark:border-white/10 dark:bg-zinc-800 p-3 space-y-2 cursor-move w-64"
                            data-task-id="{{ $task['id'] }}"
                            aria-label="Task {{ $task['id'] }}"
                        >
                            <div class="flex justify-between">
                                {{-- Checkbox --}}
                                <flux:checkbox
                                    x-data="{ id: {{ $task['id'] }} }"
                                    value="{{ $task['id'] }}"
                                    x-bind:checked="selectedIds.includes(id)"
                                    @change="e => {
                                                      if (e.target.checked) selectedIds.push(id);
                                                      else selectedIds = selectedIds.filter(i => i !== id);
                                                  }"
                                />
                                {{-- EPO Badge --}}
                                @if(($task['is_extra'] ?? false))
                                    <flux:badge color="blue" size="sm" title="Extra Purchase Order">EPO</flux:badge>
                                @endif

                                {{-- Floor Area --}}
                                <flux:badge color="green" size="sm" title="Floor Area">
                                    {{ $task['floor_area_category'] }}
                                </flux:badge>
                                {{-- Notes --}}
                                @if(($task['has_notes'] ?? false))
                                    <flux:modal.trigger name="note-{{ $task['id'] }}">
                                        <flux:button
                                            size="xs"
                                            variant="subtle"
                                            color="slate"
                                            icon="chat-bubble-left-right"
                                            title="Show latest note"
                                            wire:click="showLatestNote({{ $task['id'] }})"
                                        />
                                    </flux:modal.trigger>

                                    <flux:modal name="note-{{ $task['id'] }}" class="p4 min-w-[22rem] min-h-[4rem]">
                                        {{ $currentNote }}
                                    </flux:modal>
                                @endif
                                {{-- Task Details Button --}}
                                <flux:button
                                    variant="subtle"
                                    size="xs"
                                    icon="arrow-top-right-on-square"
                                    title="Open task"
                                    target="_blank"
                                    href="{{ route('task-details', ['task_id' => $task['id']]) }}"
                                />
                            </div>

                            <flux:heading size="sm">{{ $task['title'] }}</flux:heading>

                            <flux:text class="text-sm text-gray-600 dark:text-white/80">
                                <dl class="space-y-2">
                                    <dd class="flex items-center gap-1">
                                        <flux:icon name="home" class="w-4 h-4 text-gray-500" />
                                        {{ $task['subdivision'] ?? 'Subdivision' }} - {{ $task['lot_number'] ?? 'Lot' }}
                                    </dd>
                                    <dd class="flex items-center gap-1">
                                        <flux:icon name="map-pin" class="w-4 h-4 text-gray-500" />
                                        {{ $task['address'] ?? '' }}
                                    </dd>
                                    <dd class="flex items-center gap-1">
                                        <flux:icon name="calendar" class="w-4 h-4 text-gray-500" />
                                        {{ $task['date'] ?? '' }}
                                    </dd>
                                </dl>
                            </flux:text>

                            {{-- Tags footer: open global modal (same behavior as calendar) --}}
                            <div class="w-full mt-2 pt-2 border-t border-zinc-200 dark:border-white/10 cursor-pointer select-none"
                                 role="button"
                                 aria-label="Edit tags for task {{ $task['id'] }}"
                                 onclick="Livewire.dispatchTo('task.edit-task-tags-modal', 'open-edit-tags', { taskId: {{ $task['id'] }} })"
                            >
                                <div class="flex flex-wrap gap-1 w-full">
                                    @php
                                        // Support either an explicit 'tags' array with [name,color,time] or a simple 'tag_labels' array
                                        $hasExplicitTags = isset($task['tags']) && is_array($task['tags']) && count($task['tags']) > 0;
                                        $hasLabelTags = isset($task['tag_labels']) && is_array($task['tag_labels']) && count($task['tag_labels']) > 0;
                                    @endphp

                                    @if($hasExplicitTags)
                                        @foreach($task['tags'] as $tag)
                                            @php
                                                $time = !empty($tag['time']) ? $tag['time'] : null; // expected 'HH:MM' already
                                                $label = $time ? ($tag['name'].' @ '.$time) : $tag['name'];
                                                $color = $tag['color'] ?? null;
                                            @endphp
                                            <span
                                                class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium border border-zinc-200 dark:border-white/10"
                                                @if(!empty($color))
                                                    style="background-color: {{ $color }}22; color: {{ $color }}"
                                                @endif
                                                title="{{ $label }}"
                                            >
                                                {{ $label }}
                                            </span>
                                        @endforeach
                                    @elseif($hasLabelTags)
                                        @foreach($task['tag_labels'] as $label)
                                            <span
                                                class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium text-zinc-600 bg-zinc-100 border border-zinc-200 dark:text-zinc-300 dark:bg-zinc-700/50 dark:border-white/10"
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
                </div>
            </div>
        </div>

        {{-- Team buckets --}}
        @foreach ($teams as $team)
            <div class="flex flex-col md:flex-row gap-4 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-white/10 rounded-2xl shadow-sm p-4">
                <div class="w-full md:w-64">
                    <h3 class="font-semibold mb-2">{{ $team['name'] }}</h3>
                    @if (!empty($team['members']))
                        <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                            @foreach ($team['members'] as $member)
                                <li>{{ $member }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500">No members</p>
                    @endif
                </div>

                <div class="flex-1">
                    <div
                        class="teamTasks sortable flex flex-wrap gap-4"
                        id="team-{{ $team['id'] }}"
                        data-team-id="{{ $team['id'] }}"
                    >
                        @foreach (($tasksByTeam[$team['id']] ?? []) as $task)
                            <div
                                class="task-item bg-white rounded-lg shadow-xs border border-zinc-200 dark:border-white/10 dark:bg-zinc-800 p-3 space-y-2 cursor-move w-64"
                                data-task-id="{{ $task['id'] }}"
                            >
                                <div class="flex justify-between">
                                    <div class="flex items-start gap-2">
                                        <flux:badge color="green" size="sm" title="Floor Area">
                                            {{ $task['floor_area_category'] }}
                                        </flux:badge>
                                        {{-- Notes --}}
                                        @if(($task['has_notes'] ?? false))
                                            <flux:modal.trigger name="note-{{ $task['id'] }}">
                                                <flux:button
                                                    size="xs"
                                                    variant="subtle"
                                                    color="slate"
                                                    icon="chat-bubble-left-right"
                                                    title="Show latest note"
                                                    wire:click="showLatestNote({{ $task['id'] }})"
                                                />
                                            </flux:modal.trigger>

                                            <flux:modal name="note-{{ $task['id'] }}" class="p4 min-w-[22rem] min-h-[4rem]">
                                                {{ $currentNote }}
                                            </flux:modal>
                                        @endif
                                    </div>

                                    <flux:button
                                        variant="subtle"
                                        size="xs"
                                        icon="arrow-top-right-on-square"
                                        title="Open task"
                                        target="_blank"
                                        href="{{ route('task-details', ['task_id' => $task['id']]) }}"
                                    />
                                </div>

                                <flux:heading size="sm">{{ $task['title'] }}</flux:heading>

                                <flux:text class="text-sm text-gray-600 dark:text-white/80">
                                    <dl class="space-y-2">
                                        <dd class="flex items-center gap-1">
                                            <flux:icon name="home" class="w-4 h-4 text-gray-500" />
                                            {{ $task['subdivision'] ?? 'Subdivision' }} - {{ $task['lot_number'] ?? 'Lot' }}
                                        </dd>
                                        <dd class="flex items-center gap-1">
                                            <flux:icon name="map-pin" class="w-4 h-4 text-gray-500" />
                                            {{ $task['address'] ?? '' }}
                                        </dd>
                                        <dd class="flex items-center gap-1">
                                            <flux:icon name="calendar" class="w-4 h-4 text-gray-500" />
                                            {{ $task['date'] ?? '' }}
                                        </dd>
                                    </dl>
                                </flux:text>



                                </div>
                                {{-- Tags footer: open global modal --}}
                                <div class="w-full mt-2 pt-2 border-t border-zinc-200 dark:border-white/10 cursor-pointer select-none"
                                     role="button"
                                     aria-label="Edit tags for task {{ $task['id'] }}"
                                     onclick="Livewire.dispatchTo('task.edit-task-tags-modal', 'open-edit-tags', { taskId: {{ $task['id'] }} })"
                                >
                                    <div class="flex flex-wrap gap-1 w-full">
                                        @if(isset($task['tags']) && is_array($task['tags']) && count($task['tags']) > 0)
                                            @foreach($task['tags'] as $tag)
                                                @php
                                                    $time = !empty($tag['time']) ? $tag['time'] : null;
                                                    $label = $time ? ($tag['name'] . ' @ ' . $time) : $tag['name'];
                                                    $color = $tag['color'] ?? null;
                                                @endphp
                                                <span
                                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium border border-zinc-200 dark:border-white/10"
                                                    @if(!empty($color))
                                                        style="background-color: {{ $color }}22; color: {{ $color }}"
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

                                @if (!empty($task['external']))
                                    <flux:badge color="amber" class="mt-2" title="Workers external to the team">
                                        Ext: {{ implode(', ', $task['external']) }}
                                    </flux:badge>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </flux:checkbox.group>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('bulkAssignCheckboxes', () => ({
                selectedIds: [],
            }));
        });
    </script>
</div>
