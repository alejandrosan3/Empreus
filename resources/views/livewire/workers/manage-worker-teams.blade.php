<div>
    <flux:modal wire:model="showModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Assign Teams</flux:heading>
                <flux:subheading>Select one or more teams</flux:subheading>
            </div>

            <flux:select
                wire:model.live="teamIds"
                variant="listbox"
                multiple
                searchable
                placeholder="Choose teams..."
            >
                @foreach($teams as $team)
                    <flux:select.option value="{{ $team->id }}">{{ $team->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="flex justify-end space-x-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancel</flux:button>
            <flux:button wire:click="assign" variant="primary">Assign</flux:button>
        </div>
    </flux:modal>
</div>
