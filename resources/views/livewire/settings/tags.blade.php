<x-settings.layout heading="Tags" :subheading="'Labels '.(tenancy()->business()?->name ?? 'this business').' puts on tasks, like Walk or Closing.'">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        @if ($this->canChange() && ! $showArchived)
            <form wire:submit="add" class="flex items-end gap-2">
                <flux:input wire:model="newName" label="Add a tag" placeholder="For example: Rush" />
                <div>
                    <flux:label for="new-tag-color">Color</flux:label>
                    <input id="new-tag-color" type="color" wire:model="newColor" class="mt-2 h-10 w-14 cursor-pointer rounded border border-zinc-200 dark:border-zinc-700" />
                </div>
                <flux:button type="submit" icon="plus">Add</flux:button>
            </form>
        @else
            <div></div>
        @endif
        <flux:switch wire:model.live="showArchived" label="Show archived" />
    </div>
    <flux:error name="newName" />

    <div class="divide-y divide-zinc-200 dark:divide-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-700">
        @forelse ($this->tags as $tag)
            <div class="flex items-center justify-between gap-2 p-3" wire:key="tag-{{ $tag->id }}">
                <div class="flex items-center gap-3">
                    <span class="inline-block h-4 w-4 rounded-full" style="background-color: {{ $tag->color }}"></span>
                    <span class="font-medium">{{ $tag->name }}</span>
                    <flux:text size="sm">{{ trans_choice(':count task|:count tasks', $tag->tasks_count) }}</flux:text>
                </div>
                <div class="flex gap-2">
                    @if ($showArchived)
                        @if ($this->canChange('full'))
                            <flux:button size="sm" wire:click="restore({{ $tag->id }})">Restore</flux:button>
                        @endif
                    @else
                        @if ($this->canChange())
                            <flux:button size="sm" variant="ghost" icon="pencil" wire:click="edit({{ $tag->id }})">Edit</flux:button>
                        @endif
                        @if ($this->canChange('full'))
                            <flux:button size="sm" variant="ghost" icon="archive-box" wire:click="archive({{ $tag->id }})"
                                wire:confirm="Archive {{ $tag->name }}? It disappears from tasks until restored.">Archive</flux:button>
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <div class="p-6 text-center"><flux:text>{{ $showArchived ? 'Nothing archived.' : 'No tags yet.' }}</flux:text></div>
        @endforelse
    </div>

    <flux:modal name="tag" class="md:w-96">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">Edit tag</flux:heading>
            <flux:input wire:model="name" label="Name" />
            <div>
                <flux:label for="tag-color">Color</flux:label>
                <input id="tag-color" type="color" wire:model="color" class="mt-2 h-10 w-14 cursor-pointer rounded border border-zinc-200 dark:border-zinc-700" />
            </div>
            <div class="flex justify-end"><flux:button type="submit" variant="primary">Save</flux:button></div>
        </form>
    </flux:modal>
</x-settings.layout>
