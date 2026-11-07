<div>
    <h3>Division: </h3>
    <flux:select wire:model.change="selectedAccountId" variant="listbox" searchable placeholder="Select division...">
        @foreach ($accounts as $account)
            <flux:select.option value="{{ $account['id'] }}">
                {{ $account['label'] }}
            </flux:select.option>
        @endforeach
    </flux:select>
</div>
