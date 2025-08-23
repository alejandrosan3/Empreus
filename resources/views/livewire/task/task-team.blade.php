<div class="space-y-6">
    @foreach ($teams as $team)
        {{-- =========================
             Team Row Container
        ========================== --}}
        <div
            class="flex flex-col md:flex-row gap-4 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-white/10 rounded-2xl shadow-sm p-4"
        >
            {{-- Team info column --}}
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

            {{-- Tasks container --}}
            <div class="flex-1">
                <div
                    class="teamTasks sortable flex flex-wrap gap-4"
                    id="team-{{ $team['id'] ?? 'none' }}"
                    data-team="{{ $team['id'] ?? 'none' }}"
                >
                    @foreach ($team['tasks'] as $task)
                        {{-- Single Task Card (same style as board/calendar) --}}
                        <div
                            class="task-item bg-white rounded-lg shadow-xs border border-zinc-200 dark:border-white/10 dark:bg-zinc-800 p-3 space-y-2 cursor-move w-60"
                            data-task-id="{{ $task['id'] }}"
                        >
                            <div class="flex justify-between">
                                <div class="flex items-start gap-2">
                                    <flux:badge color="green" size="sm">FA</flux:badge>
                                </div>
                                <flux:button
                                    variant="subtle"
                                    size="xs"
                                    icon="arrow-top-right-on-square"
                                />
                            </div>

                            <flux:heading>{{ $task['title'] }}</flux:heading>

                            <flux:text class="text-sm text-gray-600 dark:text-white/80">
                                <dl class="space-y-2">
                                    <dd class="flex items-center gap-1">
                                        <flux:icon name="home" class="w-4 h-4 text-gray-500" />
                                        Subdivision - Lot
                                    </dd>
                                    <dd class="flex items-center gap-1">
                                        <flux:icon name="calendar" class="w-4 h-4 text-gray-500" />
                                        2024-01-01
                                    </dd>
                                </dl>
                            </flux:text>

                            @if (!empty($task['external']))
                                <flux:badge color="amber" class="mt-2">Ext: {{ implode(', ', $task['external']) }}</flux:badge>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
