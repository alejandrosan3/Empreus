<div class="space-y-6 p-4">
    <livewire:partials.return-button />
    
    <header>
        <flux:heading size="lg" level="2">{{ __('Create Builder') }}</flux:heading>
    </header>

    <div class="grid grid-cols-2 gap-4">
        {{-- Name --}}
        <flux:input label="Name" type="text" wire:model.defer="name" :error="$errors->first('name')" />

        {{-- Email --}}
        <flux:input label="Email" type="email" wire:model.defer="email" :error="$errors->first('email')" />

        {{-- Address (full width) --}}
        <div class="col-span-2">
            <flux:input label="Address" type="text" wire:model.defer="address" :error="$errors->first('address')" />
        </div>

        {{-- Phone --}}
        <flux:input label="Phone" type="text" wire:model.defer="phone" :error="$errors->first('phone')" />
    </div>

    <div class="flex justify-end">
        <flux:button wire:click="save" variant="primary"> {{ __('Add Builder') }} </flux:button>
    </div>
</div>