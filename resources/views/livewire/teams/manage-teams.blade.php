<div>
    <flux:modal wire:model="showModal" class="md:w-[800px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Assign Team to Task</flux:heading>
                <flux:subheading>Manage worker assignments for this task</flux:subheading>
            </div>

            {{-- Two Column Layout --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- LEFT COLUMN: Add Workers & Teams --}}
                <div class="space-y-6">
                    {{-- Individual Worker Selection --}}
                    <div>
                        <flux:field>
                            <flux:label>Add Individual Workers</flux:label>
                            <flux:select
                                    wire:model.live="selectedWorkerIds"
                                    variant="listbox"
                                    multiple
                                    searchable
                                    placeholder="Choose workers..."
                            >
                                <x-slot name="search">
                                    <flux:select.search class="px-4" placeholder="Search workers..." />
                                </x-slot>

                                @foreach($availableWorkers as $worker)
                                    <flux:select.option value="{{ $worker->id }}">
                                        {{ $worker->name }} ({{ $worker->email }})
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:description>
                                Select multiple workers to assign to this task
                            </flux:description>
                        </flux:field>
                    </div>

                    {{-- Team Selection --}}
                    <div>
                        <flux:field>
                            <flux:label>Or Select Entire Team</flux:label>
                            <div class="flex gap-2">
                                <flux:select wire:model.live="selectedTeamId" placeholder="Choose a team..." class="flex-1">
                                    @foreach($teams as $team)
                                        <flux:select.option value="{{ $team->id }}">
                                            {{ $team->name }} ({{ $team->workers->count() }} workers)
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                                @if($selectedTeamId)
                                    <flux:button
                                            wire:click="$set('selectedTeamId', null)"
                                            size="sm"
                                            variant="ghost"
                                            icon="x-mark"
                                            class="text-gray-500 hover:text-gray-700"
                                    />
                                @endif
                            </div>
                            <flux:description>
                                Selecting a team will add all team members
                            </flux:description>
                        </flux:field>
                    </div>

                    {{-- Selected Workers Preview --}}
                    @if(!empty($selectedWorkerIds))
                        <div>
                            <flux:subheading>Selected Workers Preview</flux:subheading>
                            <div class="mt-3 max-h-32 overflow-y-auto space-y-1">
                                @foreach($availableWorkers->whereIn('id', $selectedWorkerIds) as $worker)
                                    <div class="text-sm text-zinc-600 dark:text-zinc-400 p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                                        {{ $worker->name }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Team Workers Preview --}}
                    @if($selectedTeamId)
                        @php
                            $selectedTeam = $teams->find($selectedTeamId);
                        @endphp
                        @if($selectedTeam)
                            <div>
                                <flux:subheading>Selected Team Workers Preview</flux:subheading>
                                <div class="mt-3 max-h-32 overflow-y-auto space-y-1">
                                    @foreach($selectedTeam->workers as $worker)
                                        <div class="text-sm text-zinc-600 dark:text-zinc-400 p-2 bg-zinc-50 dark:bg-zinc-800 rounded">
                                            {{ $worker->name }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                {{-- RIGHT COLUMN: Currently Assigned Workers --}}
                <div class="space-y-4">
                    @if($assignedWorkers->count())
                        <div>
                            <flux:description>
                                Currently {{ $assignedWorkers->count() }} worker{{ $assignedWorkers->count() !== 1 ? 's' : '' }} assigned
                            </flux:description>
                        </div>
                    @endif

                    @if($assignedWorkers->count() > 0)
                        <div class="max-h-96 overflow-y-auto space-y-2">
                            @foreach($assignedWorkers as $worker)
                                <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium">{{ $worker->name }}</span>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $worker->email }}</span>
                                    </div>
                                    <flux:button
                                            wire:click="removeWorker({{ $worker->id }})"
                                            size="xs"
                                            variant="ghost"
                                            icon="trash"
                                            class="text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                                    />
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-zinc-500 dark:text-zinc-400">
                            <flux:icon.users class="mx-auto h-12 w-12 mb-2 opacity-50" />
                            <p class="text-sm">No workers assigned yet</p>
                            <p class="text-xs">Use the form on the left to add workers</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Modal Footer --}}
        <div class="flex justify-end space-x-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <flux:button wire:click="closeModal" variant="ghost">Cancel</flux:button>
            <flux:button
                    wire:click="assignWorkers"
                    variant="primary"
                    wire:loading.attr="disabled"
                    wire:target="assignWorkers"
            >
                <span wire:loading.remove wire:target="assignWorkers">Assign Workers</span>
                <span wire:loading wire:target="assignWorkers">Assigning...</span>
            </flux:button>
        </div>

    </flux:modal>
</div>
