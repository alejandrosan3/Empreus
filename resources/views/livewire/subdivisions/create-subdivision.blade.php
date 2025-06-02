<div class="space-y-6 p-4">
    <header>
        <flux:heading size="lg" level="2">{{ __('Create Subdivision') }}</flux:heading>
    </header>

    <div class="grid grid-cols-2 gap-4">
        {{-- Name --}}
        <flux:input label="Name" type="text" wire:model.defer="name" :error="$errors->first('name')" />
    </div>

    <div class="flex justify-start">
        <flux:button wire:click="save" variant="primary"> {{ __('Add Subdivision') }} </flux:button>
    </div>

    @persist('toast')
        <flux:toast />
    @endpersist
</div>