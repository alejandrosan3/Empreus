<section class="space-y-6 p-4">
    <header>
        <flux:heading size="lg" level="2">Add New Lot</flux:heading>
    </header>

    <div class="grid grid-cols-2 gap-4">
        {{-- Builder --}}
        <div class="col-span-2">
            <flux:select label="Builder" variant="combobox" :filter="false" wire:model.defer="builder_id" :error="$errors->first('builder_id')">
                <x-slot name="input">
                    <flux:select.input wire:model.live="search" />
                </x-slot>

                @foreach(\App\Models\Builder::all() as $b)
                    <flux:select.option
                        value="{{ $b->id }}"
                        wire:key="builder-{{ $b->id }}"
                    >
                        {{ $b->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        {{-- Subdivision --}}
        <flux:input label="Subdivision" type="text" wire:model.defer="subdivision" :error="$errors->first('subdivision')" />

        {{-- Lot Number --}}
        <flux:input label="Lot #" type="text" wire:model.defer="lot_number" :error="$errors->first('lot_number')" />

        {{-- Lot Phase --}}
        <flux:input label="Phase" type="text" wire:model.defer="phase" :error="$errors->first('phase')" />

        {{-- Supervisor --}}
        <flux:input label="Supervisor" type="text" wire:model.defer="supervisor" :error="$errors->first('supervisor')" />

        {{-- Address --}}
        <div class="col-span-2">
            <flux:input label="Address" type="text" wire:model.defer="address" :error="$errors->first('address')" />
        </div>

        {{-- Stories --}}
        <flux:input label="Stories" type="text" wire:model.defer="num_of_floor" :error="$errors->first('num_of_floor')" />

        {{-- SQFT --}}
        <flux:input label="Supervisor" type="text" wire:model.defer="floor_area" :error="$errors->first('floor_area')" />
    </div>

    <div class="flex justify-end">
        <flux:button wire:click="save" variant="primary">Add lot</flux:button>
    </div>
</section>