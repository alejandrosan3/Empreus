<div>
    <flux:modal name="bulkEditModal" wire:model.self="showModal" class="p-6">
        <flux:heading level="2" size="xl" class="text-zinc-500">Bulk Edit</flux:heading>
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


        {{-- Group 2: Date Pickers --}}
        <flux:separator text="Dates Section" class="my-8" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <flux:date-picker label="Planned Start Date" wire:model="scheduled_start_date" selectable-header clearable />
            <flux:date-picker label="Start Date" wire:model="started_at" selectable-header clearable />
            <flux:date-picker label="Scheduled End Date" wire:model="scheduled_end_date" selectable-header clearable />
            <flux:date-picker label="End Date" wire:model="ended_at" selectable-header clearable />
        </div>

        <flux:button wire:click="save" variant="primary">Apply Changes</flux:button>
    </flux:modal>
</div>
