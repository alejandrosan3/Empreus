<div class="space-y-6 p-4">
    <livewire:partials.return-button />

    <header>
        <flux:heading size="lg" level="2">{{ __('Create Worker') }}</flux:heading>
    </header>

    <div class="grid grid-cols-2 gap-4">
        <flux:input label="Name" type="text" wire:model.defer="name" :error="$errors->first('name')" />
        <flux:input label="Email" type="email" wire:model.defer="email" :error="$errors->first('email')" />
    </div>

    <div class="flex justify-end">
        <flux:button wire:click="save" variant="primary"> {{ __('Add Worker') }} </flux:button>
    </div>
</div>