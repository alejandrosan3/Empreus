<div>
    <h3>Account: </h3>
    <flux:select wire:model.change="selectedAccountId" variant="listbox" searchable placeholder="Select account...">
        @foreach ($accounts as $account)
            <flux:option value="{{ $account->id }}">
                {{ $account->name }}
            </flux:option>
        @endforeach
    </flux:select>

<p wire:key={{ $selectedAccountId }}>Selected Account ID: {{ $selectedAccountId }} </p>

</div>