<div>
    @if (!$selectedLot)

        <flux:table :paginate="$lots">
            <!-- Table Header -->
            <flux:columns>
                <flux:column sortable :sorted="$sortBy === 'builder'" wire:click="sort('builder')">Builder</flux:column>
                <flux:column sortable :sorted="$sortBy === 'subdivision'" wire:click="sort('subdivision')">Subdivision</flux:column>
                <flux:column sortable :sorted="$sortBy === 'lot'" wire:click="sort('lot_number')">Lot</flux:column>
                <flux:column>Stories</flux:column>
                <flux:column sortable :sorted="$sortBy === 'last_completed_date'" wire:click="sort('last_completed_date')">Last Completed Task</flux:column>
                @foreach ($taskTypes as $taskType)
                    <flux:column>{{ $taskType }}</flux:column>
                @endforeach
                <flux:column>EPOs</flux:column>
                {{-- <flux:column sortable :sorted="$sortBy === 'status'" wire:click="sort('status')">Progress</flux:column> --}}
                <flux:column>Actions</flux:column>
            </flux:columns>

            <!-- Table Content -->
            <flux:rows>
                @foreach ($lots as $lot)
                    <flux:row :key="$lot->id">
                        <flux:cell>{{ $lot->builder->name }}</flux:cell>
                        <flux:cell>{{ $lot->subdivision . ($lot->phase ? " ({$lot->phase})" : "") }}</flux:cell>
                        <flux:cell>{{ $lot->lot_number }}</flux:cell>
                        <flux:cell>{{ $lot->num_of_floors ?? '-' }}</flux:cell>
                        <!-- Last completed task section -->
                        <flux:cell>
                            @php
                                $lastCompleted = $lot->getLastCompletedTask();

                            @endphp

                            @if($lastCompleted)
                                <!-- we have a task, show the task's name and date -->
                                {{ $lastCompleted->taskType->name }} ({{ $lastCompleted->ended_at }})
                            @else
                                <!-- no tasks completed yet -->
                                <em>No tasks completed yet</em>
                            @endif
                        </flux:cell>

                        @foreach ($taskTypes as $taskType)
                            @if($task = $lot->getTaskByTypeName($taskType))
                                <flux:cell>
                                    @if($task->status->isCompleted())
                                        <flux:tooltip content="Completed on: {{ $task->ended_at }} ">
                                            <flux:checkbox checked disabled />
                                        </flux:tooltip>
                                    @else
                                        <flux:checkbox disabled />
                                    @endif
                                </flux:cell>
                            @else
                                <flux:cell> - </flux:cell>
                            @endif
                        @endforeach
                        <!-- EPO count section -->
                        <flux:cell>
                            @php
                                $epoCount = $this->epoCount($lot);
                            @endphp
                            <flux:heading class="flex items-center gap-2">
                                {{ $epoCount['completed'] }}/{{ $epoCount['total'] }}
                                <flux:tooltip toggleable>
                                    <flux:button icon="information-circle" size="sm" variant="ghost" />

                                    <flux:tooltip.content class="max-w-[20rem] space-y-2">
                                        <div class="flex flex-col space-y-1">
                                            @foreach($lot->tasks->where('is_extra', 1) as $task)
                                                <div class="flex items-center space-x-2">
                                                    @if($task->ended_at)
                                                        <flux:icon.check variant="micro" class="text-green-500 dark:text-green-300" />
                                                    @else
                                                        <flux:icon.clock variant="micro" class="text-amber-500 dark:text-amber-300" />
                                                    @endif
                                                    <span>{{ $task->taskType->name }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </flux:tooltip.content>
                                </flux:tooltip>
                            </flux:heading>
                        </flux:cell>


                       {{-- <flux:cell>
                            <flux:badge :color="$lot->status->color" variant="pill" size="lg">
                                {{ $lot->status->name }}
                            </flux:badge>
                        </flux:cell>
                        --}}

                        <!-- Action Items -->
                        <flux:cell>
                            <flux:dropdown>
                                <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                                <flux:menu>
                                    <flux:menu.item icon="clipboard-document-check" wire:click="loadDetails( {{ $lot->id }} )" > Details</flux:menu.item>

                                </flux:menu>
                            </flux:dropdown>
                        </flux:cell>
                    </flux:row>
                @endforeach
            </flux:rows>
        </flux:table>
    @else
        <!-- Render the ContentDetails component dynamically -->
        <div>
            @livewire('pages.lot-details', ['lot' => $selectedLot])
        </div>
    @endif

</div>
