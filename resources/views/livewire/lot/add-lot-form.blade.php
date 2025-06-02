<div class="p-4">
    <flux:button icon="arrow-left" variant="ghost" wire:click="goBack">
        Back
    </flux:button>

    {{-- Validation errors (will show once we add rules) --}}
    @if ($errors->any())
        <div class="mb-4 text-red-600">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <flux:heading size="xl" class="mb-4"> Add Lot <flux:badge color="yellow" inset="top bottom">{{ $companyName }}</flux:badge> </flux:heading>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        {{-- Builder / Subdivision / Lot --}}
        <livewire:partials.cards :items="$card1" mode="add" :key="'card1-' . $builder_id" />

        {{-- Address / Supervisor / Phase --}}
        <livewire:partials.cards :items="$card2" mode="add" />

        {{-- Floor Number and SQFT --}}
        <livewire:partials.cards :items="$card3" mode="add" />
    </div>

    <flux:button variant="primary" class="w-full mt-4" wire:click.prevent="save">
        Add Lot
    </flux:button>

    @persist('toast')
        <flux:toast />
    @endpersist
</div>
