<x-settings.layout heading="Task types & prices" :subheading="'The kinds of tasks '.(tenancy()->business()?->name ?? 'this business').' does'.(Gate::allows('see-money') ? ', and what they cost.' : '.')">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search task types" class="max-w-xs" />
        <flux:switch wire:model.live="showArchived" label="Show archived" />
    </div>

    @if ($this->canChange() && ! $showArchived)
        <form wire:submit="add" class="flex flex-col gap-2 sm:flex-row sm:items-end">
            <flux:input wire:model="newName" label="Add a task type" placeholder="For example: Final clean" />
            @can('see-money')
                <flux:input wire:model="newPrice" label="Default price" type="number" step="0.01" min="0" class="sm:w-36" />
            @endcan
            <flux:button type="submit" icon="plus">Add</flux:button>
        </form>
        <flux:error name="newName" />
        <flux:error name="newPrice" />
    @endif

    <div class="divide-y divide-zinc-200 dark:divide-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-700">
        @forelse ($this->taskTypes as $type)
            <div class="flex items-center justify-between gap-2 p-3" wire:key="type-{{ $type->id }}">
                <div>
                    <div class="font-medium">{{ $type->name }}</div>
                    @can('see-money')
                        <flux:text size="sm">
                            {{ $type->default_price !== null ? 'Default '.money($type->default_price) : 'No default price' }}
                            @if ($type->prices_count) · {{ trans_choice(':count builder price|:count builder prices', $type->prices_count) }} @endif
                        </flux:text>
                    @endcan
                </div>
                <div class="flex gap-2">
                    @if ($showArchived)
                        @if ($this->canChange('full'))
                            <flux:button size="sm" wire:click="restore({{ $type->id }})">Restore</flux:button>
                        @endif
                    @else
                        <flux:button size="sm" variant="ghost" icon="pencil" wire:click="edit({{ $type->id }})">{{ $this->canChange() ? 'Edit' : 'Open' }}</flux:button>
                        @if ($this->canChange('full'))
                            <flux:button size="sm" variant="ghost" icon="archive-box" wire:click="archive({{ $type->id }})"
                                wire:confirm="Archive {{ $type->name }}? Existing tasks keep it.">Archive</flux:button>
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <div class="p-6 text-center"><flux:text>{{ $showArchived ? 'Nothing archived.' : 'No task types yet.' }}</flux:text></div>
        @endforelse
    </div>

    <flux:modal name="task-type" class="md:w-[32rem]">
        @if ($this->editing)
            <div class="space-y-6">
                <form wire:submit="save" class="space-y-4">
                    <flux:heading size="lg">{{ $this->editing->name }}</flux:heading>
                    <fieldset @disabled(! $this->canChange()) class="space-y-4">
                        <flux:input wire:model="name" label="Name" />
                        <flux:input wire:model="description" label="Description" />
                        @can('see-money')
                            <flux:input wire:model="defaultPrice" label="Default price" type="number" step="0.01" min="0" description="Used for new tasks when the builder has no price of its own." />
                        @endcan
                    </fieldset>
                    @if ($this->canChange())
                        <div class="flex justify-end"><flux:button type="submit" variant="primary">Save</flux:button></div>
                    @endif
                </form>

                @can('see-money')
                    <div class="space-y-3">
                        <flux:heading>Builder prices</flux:heading>
                        @forelse ($this->editing->prices->sortBy('builder.name') as $price)
                            <div class="flex items-center justify-between" wire:key="price-{{ $price->id }}">
                                <span>{{ $price->builder?->name }}</span>
                                <div class="flex items-center gap-2">
                                    <span>{{ money($price->price) }}</span>
                                    @if ($this->canChange())
                                        <flux:button size="xs" variant="ghost" icon="x-mark" wire:click="removeBuilderPrice({{ $price->id }})" aria-label="Remove" />
                                    @endif
                                </div>
                            </div>
                        @empty
                            <flux:text>Every builder pays the default price.</flux:text>
                        @endforelse

                        @if ($this->canChange())
                            <form wire:submit="setBuilderPrice" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                                <flux:select wire:model="priceBuilderId" placeholder="Pick a builder" label="Builder" class="sm:flex-1">
                                    @foreach ($this->builders as $builder)
                                        <flux:select.option value="{{ $builder->id }}">{{ $builder->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:input wire:model="builderPrice" type="number" step="0.01" min="0" label="Price" class="sm:w-32" />
                                <flux:button type="submit">Set</flux:button>
                            </form>
                            <flux:error name="priceBuilderId" />
                            <flux:error name="builderPrice" />
                        @endif
                    </div>
                @endcan
            </div>
        @endif
    </flux:modal>
</x-settings.layout>
