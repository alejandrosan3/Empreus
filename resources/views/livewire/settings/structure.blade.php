<x-settings.layout heading="Businesses & divisions" subheading="A business is a line of work (Cleaning, Painting). A division is a branch of it (Jacksonville, Volusia).">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        @if ($this->can('edit'))
            <form wire:submit="addBusiness" class="flex items-end gap-2">
                <flux:input wire:model="newBusiness" label="Add a business" placeholder="For example: Painting" />
                <flux:button type="submit" icon="plus">Add</flux:button>
            </form>
        @endif
        <flux:switch wire:model.live="showArchived" label="Show archived" />
    </div>
    <flux:error name="newBusiness" />
    <flux:error name="structure" />

    @foreach ($this->businesses as $business)
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700" wire:key="business-{{ $business->id }}">
            <div class="flex items-center justify-between gap-2 border-b border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center gap-2">
                    <flux:heading size="lg" @class(['text-zinc-400 line-through' => $business->trashed()])>{{ $business->name }}</flux:heading>
                    @if ($business->trashed()) <flux:badge size="sm">Archived</flux:badge> @endif
                </div>
                <div class="flex gap-2">
                    @if ($business->trashed())
                        @if ($this->can('full'))
                            <flux:button size="sm" wire:click="restore('business', {{ $business->id }})">Restore</flux:button>
                        @endif
                    @else
                        @if ($this->can('edit', $business))
                            <flux:button size="sm" variant="ghost" icon="pencil" wire:click="startRename('business', {{ $business->id }})">Rename</flux:button>
                        @endif
                        @if ($this->can('full'))
                            <flux:button size="sm" variant="ghost" icon="archive-box" wire:click="archive('business', {{ $business->id }})"
                                wire:confirm="Archive {{ $business->name }}?">Archive</flux:button>
                        @endif
                    @endif
                </div>
            </div>

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($business->divisions as $division)
                    <div class="flex items-center justify-between gap-2 px-4 py-2" wire:key="division-{{ $division->id }}">
                        <div class="flex items-center gap-2">
                            <span @class(['text-zinc-400 line-through' => $division->trashed()])>{{ $division->name }}</span>
                            @if ($division->trashed()) <flux:badge size="sm">Archived</flux:badge> @endif
                            @if ($division->id === tenancy()->divisionId()) <flux:badge size="sm" color="blue">You're here</flux:badge> @endif
                        </div>
                        <div class="flex gap-2">
                            @if ($division->trashed())
                                @if ($this->can('full', $business))
                                    <flux:button size="sm" wire:click="restore('division', {{ $division->id }})">Restore</flux:button>
                                @endif
                            @else
                                @if ($this->can('edit', $division))
                                    <flux:button size="sm" variant="ghost" icon="pencil" wire:click="startRename('division', {{ $division->id }})">Rename</flux:button>
                                @endif
                                @if ($this->can('full', $business))
                                    <flux:button size="sm" variant="ghost" icon="archive-box" wire:click="archive('division', {{ $division->id }})"
                                        wire:confirm="Archive {{ $division->name }}? Its lots and tasks are kept, and nobody can switch to it until it's restored.">Archive</flux:button>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if (! $business->trashed() && $this->can('edit', $business))
                <form wire:submit="addDivision({{ $business->id }})" class="flex items-end gap-2 border-t border-zinc-200 p-4 dark:border-zinc-700">
                    <flux:input wire:model="newDivision.{{ $business->id }}" placeholder="New division in {{ $business->name }}" />
                    <flux:button type="submit" icon="plus">Add division</flux:button>
                </form>
                <div class="px-4 pb-2"><flux:error name="newDivision.{{ $business->id }}" /></div>
            @endif
        </div>
    @endforeach

    <flux:modal name="rename" class="md:w-96">
        <form wire:submit="rename" class="space-y-4">
            <flux:heading size="lg">Rename</flux:heading>
            <flux:input wire:model="renameTo" label="New name" />
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</x-settings.layout>
