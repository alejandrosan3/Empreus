<div>
    <flux:modal name="dbl-click-modal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Field</flux:heading>
                <flux:text class="mt-2">Update the selected content.</flux:text>
            </div>

            @if($fieldType === 'input')
                <flux:input wire:model="fieldValue" label="Field Content" />
            @elseif($fieldType === 'select')
                <flux:select wire:model="fieldValue" variant="listbox" searchable clearable>


                    @foreach($selectOptions as $key => $label)
                        <flux:select.option value="{{ $key }}" > {{ $label }} </flux:select.option>
                    @endForeach
                </flux:select>

            @elseif($fieldType === 'date')
                <flux:date-picker class="size-auto" wire:model="fieldValue" label="Select the date" clearable with-inputs with-today />
            @endif

            <div class="flex">
                <flux:spacer />
                <flux:button wire:click="save" variant="primary">Save changes</flux:button>
            </div>
        </div>
    </flux:modal>

</div>
