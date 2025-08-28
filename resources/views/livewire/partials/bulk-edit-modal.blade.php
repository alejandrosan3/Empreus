<div>
    <flux:modal name="bulkEditModal" wire:model.self="showModal" class="p-6">
        <flux:heading level="2" size="xl" class="text-zinc-500">Bulk Edit</flux:heading>
        <flux:text class="mt-2">Apply changes to {{ count($taskIds) }} selected {{ $entity === 'task' ? 'task(s)' : $entity }}.</flux:text>
        <flux:heading level="3" size="lg" class="text-zinc-500 mb-8">
            You can edit the fields you want to update or leave them untouched/empty to ignore the update
        </flux:heading>

        {{-- Group 1: Inputs and Selects --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            {{-- Status --}}
            <flux:select label="Status" wire:model="status_type_id" placeholder="Edit Status" variant="listbox" searchable clearable>
                <flux:select.option value="">— Don’t update —</flux:select.option>
                @foreach($statusTypes as $id => $label)
                    <flux:select.option value="{{ $id }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            {{-- Supervisor --}}
            <flux:select label="Supervisor" wire:model="supervisor_id" placeholder="Edit Supervisor" variant="listbox" searchable clearable>
                <flux:select.option value="">— Don’t update —</flux:select.option>
                @foreach($supervisors as $id => $label)
                    <flux:select.option value="{{ $id }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            {{-- Order Type --}}
            <flux:select label="Order Type" wire:model="is_extra" placeholder="Edit Order Type" variant="listbox" searchable clearable>
                <flux:select.option value="">— Don’t update —</flux:select.option>
                <flux:select.option value="0">Standard</flux:select.option>
                <flux:select.option value="1">EPO</flux:select.option>
            </flux:select>

            {{-- Address --}}
            <flux:input wire:model.defer="address" label="Address" placeholder="Edit Address" clearable/>
        </div>

        @if($entity === 'task')
            {{-- Add Tags (add-only; keeps existing tags intact) --}}
            <flux:fieldset>
                <flux:legend>Add Tags</flux:legend>
                <flux:text size="sm" class="text-zinc-500">
                    Select one or more tags to add to all selected tasks. Existing tags will not be removed.
                </flux:text>

                {{-- Checkbox group for tags (live-bound) --}}
                <div class="mt-3">
                    <flux:label>Select tags</flux:label>
                    <flux:checkbox.group
                        wire:model.live="selectedTags"
                        class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2"
                    >
                        @foreach($allTags as $t)
                            <label class="flex items-center gap-2 rounded-md px-2 py-1" wire:key="tag-opt-{{ $t['id'] }}">
                                <flux:checkbox value="{{ (int) $t['id'] }}" />
                                <span
                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium"
                                    @if(!empty($t['color'])) style="background-color: {{ $t['color'] }}22; color: {{ $t['color'] }}" @endif
                                    >
                                        {{ $t['name'] }}
                                    </span>
                            </label>
                        @endforeach
                    </flux:checkbox.group>
                </div>

                {{-- Optional time per selected tag --}}
                @if(is_array($selectedTags) && count($selectedTags) > 0)
                    <div class="mt-4 space-y-2">
                        <flux:label>Optional time per tag</flux:label>
                        <div class="space-y-2">
                            @foreach($selectedTags as $tid)
                                @php
                                    $tag = collect($allTags)->firstWhere('id', (int) $tid);
                                @endphp
                                @if($tag)
                                    <div class="flex items-center gap-3" wire:key="tag-time-{{ $tid }}">
                                            <span
                                                class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium"
                                                @if(!empty($tag['color'])) style="background-color: {{ $tag['color'] }}22; color: {{ $tag['color'] }}" @endif
                                            >
                                                {{ $tag['name'] }}
                                            </span>
                                        <flux:input
                                            type="time"
                                            class="w-36"
                                            wire:model.lazy="tagTimes.{{ $tid }}"
                                            placeholder="--:--"
                                        />
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        <flux:text size="xs" class="text-zinc-500">
                            If set, this time will be applied to the tag on every selected task.
                        </flux:text>
                    </div>
                @endif
            </flux:fieldset>
        @endif


        {{-- Group 2: Date Pickers --}}
        <flux:separator text="Dates Section" class="my-8" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <flux:date-picker label="Planned Start Date" wire:model="scheduled_start_date" with-today with-inputs clearable />
            <flux:date-picker label="Start Date" wire:model="started_at" with-today with-inputs clearable />
            <flux:date-picker label="Scheduled End Date" wire:model="scheduled_end_date" with-today with-inputs clearable />
            <flux:date-picker label="End Date" wire:model="ended_at" clearable with-today with-inputs />
        </div>

        <flux:button wire:click="save" variant="primary">Apply Changes</flux:button>
    </flux:modal>
</div>
