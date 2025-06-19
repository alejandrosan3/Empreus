<div>
    <flux:modal wire:model="showModal" class="p-4 md:w-[28rem]">
        <flux:heading size="xl" class="mb-4">Duplicate Lot</flux:heading>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <livewire:partials.cards :items="$card1" mode="view" />
            <livewire:partials.cards :items="$card2" mode="add" :parent="$this" />
        </div>
        <flux:button variant="primary" class="w-full" wire:click="save">Create Lot</flux:button>
    </flux:modal>
</div>
