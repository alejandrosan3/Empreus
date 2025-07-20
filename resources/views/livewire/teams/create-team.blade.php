<div class="space-y-6 p-4">
    <livewire:partials.return-button />

    <header>
        <flux:heading size="lg" level="2">{{ __('Create Team') }}</flux:heading>
    </header>

    <div class="grid gap-4">
        <flux:input label="Team Name" type="text" wire:model.defer="name" :error="$errors->first('name')" />

        <flux:field>
            <flux:label>Assign Workers</flux:label>
            <flux:select
                wire:model.live="workerIds"
                variant="listbox"
                multiple
                searchable
                placeholder="Choose workers..."
            >
                <x-slot name="search">
                    <flux:select.search class="px-4" placeholder="Search workers..." />
                </x-slot>

                @foreach($workers as $worker)
                    <flux:select.option value="{{ $worker->id }}">
                        {{ $worker->name }} ({{ $worker->email }})
                    </flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>
    </div>

    <div class="flex justify-end">
        <flux:button wire:click="save" variant="primary"> {{ __('Add Team') }} </flux:button>
    </div>
</div>