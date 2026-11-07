<x-settings.layout heading="Roles" subheading="A role says what people can see and do. Give roles to people on the People page.">
    @can('edit-people')
        <div>
            <flux:modal.trigger name="new-role">
                <flux:button variant="primary" icon="plus" wire:click="$set('startFrom', '')">New role</flux:button>
            </flux:modal.trigger>
        </div>
    @endcan

    <div class="divide-y divide-zinc-200 dark:divide-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-700">
        @foreach ($this->roles as $role)
            <div class="flex flex-col gap-2 p-4 sm:flex-row sm:items-start sm:justify-between" wire:key="role-{{ $role->id }}">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('settings.roles.edit', $role) }}" wire:navigate class="font-medium hover:underline">{{ $role->name }}</a>
                        @if ($role->is_owner)
                            <flux:badge size="sm" color="amber">Always everything</flux:badge>
                        @endif
                        <flux:badge size="sm">{{ trans_choice(':count person|:count people', $role->assignments_count) }}</flux:badge>
                    </div>
                    <flux:text class="mt-1">{{ $role->summary() }}</flux:text>
                </div>
                <div class="flex shrink-0 gap-2">
                    <flux:button size="sm" :href="route('settings.roles.edit', $role)" wire:navigate>
                        {{ Gate::allows('edit-people') && ! $role->is_owner ? 'Edit' : 'Open' }}
                    </flux:button>
                    @can('platform')
                        <flux:button size="sm" variant="ghost" icon="book-open" wire:click="saveToLibrary({{ $role->id }})">Save to library</flux:button>
                    @endcan
                    @if (! $role->is_owner)
                        @can('full-people')
                            <flux:button size="sm" variant="ghost" icon="archive-box" wire:click="archive({{ $role->id }})"
                                wire:confirm="Archive {{ $role->name }}? You can restore it later.">Archive</flux:button>
                        @endcan
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if ($this->archived->isNotEmpty())
        <div>
            <flux:switch wire:model.live="showArchived" label="Show archived roles" />
            @if ($showArchived)
                <div class="mt-3 divide-y divide-zinc-200 dark:divide-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    @foreach ($this->archived as $role)
                        <div class="flex items-center justify-between p-3" wire:key="archived-{{ $role->id }}">
                            <span class="text-zinc-500">{{ $role->name }}</span>
                            @can('full-people')
                                <flux:button size="sm" wire:click="restore({{ $role->id }})">Restore</flux:button>
                            @endcan
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if ($this->library->isNotEmpty())
        <div>
            <flux:heading size="lg">Role library</flux:heading>
            <flux:subheading>Ready-made roles you can copy into your organization and then change.</flux:subheading>
            <div class="mt-3 divide-y divide-zinc-200 dark:divide-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-700">
                @foreach ($this->library as $template)
                    <div class="flex flex-col gap-2 p-4 sm:flex-row sm:items-start sm:justify-between" wire:key="lib-{{ $template->id }}">
                        <div>
                            <div class="font-medium">{{ $template->name }}</div>
                            <flux:text class="mt-1">{{ $template->summary() }}</flux:text>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            @can('edit-people')
                                <flux:button size="sm" wire:click="useTemplate({{ $template->id }})">Use this</flux:button>
                            @endcan
                            @can('platform')
                                <flux:button size="sm" variant="ghost" wire:click="removeFromLibrary({{ $template->id }})"
                                    wire:confirm="Remove {{ $template->name }} from the library? Copies already made stay.">Remove</flux:button>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <flux:modal name="new-role" class="md:w-96">
        <form wire:submit="createRole" class="space-y-4">
            <flux:heading size="lg">New role</flux:heading>
            <flux:input wire:model="newName" label="Name" placeholder="For example: Overseer" />
            <flux:select wire:model="startFrom" label="Start from">
                <flux:select.option value="">Nothing (everything hidden)</flux:select.option>
                @foreach ($this->roles as $role)
                    <flux:select.option value="{{ $role->id }}">A copy of {{ $role->name }}</flux:select.option>
                @endforeach
                @foreach ($this->library as $template)
                    <flux:select.option value="library:{{ $template->id }}">Library: {{ $template->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:text>You'll pick exactly what it can see and do on the next screen.</flux:text>
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">Create and edit</flux:button>
            </div>
        </form>
    </flux:modal>
</x-settings.layout>
