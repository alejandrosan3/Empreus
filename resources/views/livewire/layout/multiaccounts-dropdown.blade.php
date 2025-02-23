<div>
    <h3>Account: </h3>
    <flux:select wire:model.change="selectedAccountId" variant="listbox" searchable placeholder="Select account...">
        @foreach ($accounts as $account)
            <flux:select.option value="{{ $account->id }}">
                {{ $account->name }}
            </flux:select.option>
        @endforeach
    </flux:select>
</div>
