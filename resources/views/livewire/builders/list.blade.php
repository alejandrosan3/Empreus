<flux:table :paginate="$this->builders">
    <flux:columns>
        <flux:column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">Builder</flux:column>
        <flux:column>Email</flux:column>
        <flux:column sortable :sorted="$sortBy === 'address'" :direction="$sortDirection" wire:click="sort('status')">Address</flux:column>
        <flux:column sortable :sorted="$sortBy === 'phone'" :direction="$sortDirection" wire:click="sort('amount')">Phone</flux:column>
    </flux:columns>

    <flux:rows>
        @foreach ($this->builders as $builder)
            <flux:row :key="$builder->id">
                <flux:cell> {{ $builder->name }}</flux:cell>

                <flux:cell>{{ $builder->email }}</flux:cell>

                <flux:cell>{{ $builder->address }}</flux:cell>

                <flux:cell>{{ $builder->phone }}</flux:cell>

                <flux:cell>
                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom"></flux:button>
                </flux:cell>
            </flux:row>
        @endforeach
    </flux:rows>
</flux:table>