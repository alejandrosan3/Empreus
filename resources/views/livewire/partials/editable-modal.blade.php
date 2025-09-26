<div>
    <flux:modal name="dbl-click-modal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    @if($modelClass === \App\Models\TaskNote::class)
                        {{ $createNewNote ? 'Add Note' : 'Edit Note' }}
                    @else
                        Edit Field
                    @endif
                </flux:heading>
                <flux:text class="mt-2">
                    @if($modelClass === \App\Models\TaskNote::class)
                        {{ $createNewNote ? 'Create a new note for this task.' : 'Update the latest note for this task.' }}
                    @else
                        Update the selected content.
                    @endif
                </flux:text>
            </div>

            {{-- Keep ONLY the field-level toggle. Remove any header-level toggle and radio group --}}
            @if($fieldType === 'input')
                @if($modelClass === \App\Models\TaskNote::class)
                    <div class="flex items-end gap-2">
                        <div class="grow">
                            <flux:input
                                wire:model="fieldValue"
                                label="Note"
                                placeholder="{{ $createNewNote ? 'Type a new note…' : 'Update the latest note…' }}"
                            />
                        </div>
                        <flux:tooltip :content="$createNewNote ? 'Switch to edit latest' : 'Switch to add new note'">
                            <flux:button
                                type="button"
                                size="sm"
                                variant="ghost"
                                :icon="$createNewNote ? 'pencil-square' : 'plus'"
                                wire:click="toggleCreateNewNote"
                            />
                        </flux:tooltip>
                    </div>
                @else
                    <flux:input
                        wire:model="fieldValue"
                        label="Field Content"
                    />
                @endif
            @elseif($fieldType === 'select')
                <flux:select wire:model="fieldValue" variant="listbox" searchable clearable>
                    @foreach($selectOptions as $key => $label)
                        <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                    @endForeach
                </flux:select>

            @elseif($fieldType === 'date')
                <flux:date-picker class="size-auto" wire:model="fieldValue" label="Select the date" clearable with-inputs with-today />
            @endif

            <div class="flex">
                <flux:spacer />
                <flux:button wire:click="save" variant="primary">
                    @if($modelClass === \App\Models\TaskNote::class)
                        {{ $createNewNote ? 'Add Note' : 'Save Note' }}
                    @else
                        Save changes
                    @endif
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
