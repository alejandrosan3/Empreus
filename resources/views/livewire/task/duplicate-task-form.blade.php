<div>
    <flux:modal wire:model="showModal" class="p-4 md:w-[32rem]">
        <flux:heading size="xl" class="mb-4">Duplicate Task</flux:heading>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <livewire:partials.cards :items="$card1" mode="view" />
            <livewire:partials.cards :items="$card2" mode="add" :parent="$this" />
            <livewire:partials.cards :items="$card3" mode="add" :parent="$this" />
        </div>
        <livewire:partials.task-notes :note="$note" mode="add" />
        <flux:button variant="primary" class="w-full mt-4" wire:click="save">Create Task</flux:button>
    </flux:modal>
</div>
