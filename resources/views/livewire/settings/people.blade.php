<x-settings.layout heading="People" subheading="Everyone who can sign in to this organization, and what they can do where.">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-1 items-center gap-4">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search by name or email" class="max-w-sm" />
            <flux:switch wire:model.live="showDeactivated" label="Show closed accounts" />
        </div>
        @can('edit-people')
            <flux:button variant="primary" icon="user-plus" wire:click="openInvite">Invite someone</flux:button>
        @endcan
    </div>

    <div class="divide-y divide-zinc-200 dark:divide-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-700">
        @forelse ($this->people as $person)
            <a href="{{ route('settings.people.show', $person) }}" wire:navigate wire:key="person-{{ $person->id }}"
               class="flex flex-col gap-1 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 font-medium">
                        {{ $person->name }}
                        @if ($person->isDeactivated())
                            <flux:badge size="sm" color="zinc">Closed</flux:badge>
                        @endif
                        @if ($person->isPlatformAdmin())
                            <flux:badge size="sm" color="purple">Platform admin</flux:badge>
                        @endif
                    </div>
                    @can('see-contacts')
                        <flux:text size="sm">{{ $person->email }}</flux:text>
                    @endcan
                </div>
                <div class="text-sm text-zinc-600 dark:text-zinc-300 sm:text-right">
                    @foreach ($person->roleAssignments as $line)
                        <div>{{ $line->role?->name ?? 'Archived role' }} <span class="text-zinc-400">in</span> {{ $line->placeLabel() }}</div>
                    @endforeach
                </div>
            </a>
        @empty
            <div class="p-6 text-center">
                <flux:text>Nobody matches that search.</flux:text>
            </div>
        @endforelse
    </div>

    {{ $this->people->links() }}

    <flux:modal name="invite" class="md:w-[28rem]">
        <form wire:submit="invite" class="space-y-4">
            <flux:heading size="lg">Invite someone</flux:heading>
            <flux:text>They get an email with a link to choose their password. If they already have an account, they just get the new access.</flux:text>
            <flux:input wire:model="name" label="Name" />
            <flux:input wire:model="email" type="email" label="Email" />
            <flux:select wire:model="roleId" label="Role" placeholder="Pick a role">
                @foreach ($this->grantableRoles as $role)
                    <flux:select.option value="{{ $role->id }}">{{ $role->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="place" label="Where">
                @foreach ($this->places as $p)
                    <flux:select.option value="{{ $p['scope_type'] }}:{{ $p['scope_id'] }}">{{ str_repeat('— ', $p['depth']) }}{{ $p['label'] }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:text size="sm">Access flows down: a role for a whole business covers all its divisions.</flux:text>
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">Send invite</flux:button>
            </div>
        </form>
    </flux:modal>
</x-settings.layout>
