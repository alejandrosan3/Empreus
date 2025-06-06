<div class="p-4">
    <flux:button
        icon="arrow-left"
        variant="ghost"
        wire:click="goBack">
        Back
    </flux:button>

    @if($errors->any())
    <div class="mb-4 text-red-600">
        <ul class="list-disc pl-5">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
        </ul>
    </div>
    @endif
    <flux:heading size="xl" class="mb-4">Add Task <flux:badge color="yellow" inset="top bottom">{{ $companyName }}</flux:badge> </flux:heading>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        {{-- Builder and task details section --}}
        <livewire:partials.cards :items="$card1" mode="add" :key="'card1-' . $builder_id . '-sub-' . $subdivision" />
        <livewire:partials.cards :items="$card2" mode="add" />
        <livewire:partials.cards :items="$card3" mode="add" />
    </div>
    <div class="grid grid-cols-1 gap-4 mt-4">
        {{-- Note Section --}}
        <livewire:partials.task-notes :note="$note" mode="add" />
    </div>

    <flux:button variant="primary" class="w-full" wire:click.prevent='save'>Add Task</flux:button>

    @persist('toast')
        <flux:toast />
    @endpersist
</div>
