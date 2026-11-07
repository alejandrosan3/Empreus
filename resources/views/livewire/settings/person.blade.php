<x-settings.layout :heading="$person->name" :subheading="Gate::allows('see-contacts') ? $person->email : null">
    <div class="flex flex-wrap items-center gap-2">
        <flux:button size="sm" variant="ghost" icon="arrow-left" :href="route('settings.people')" wire:navigate>All people</flux:button>
        @if ($person->isDeactivated())
            <flux:badge color="zinc">Account closed {{ $person->deactivated_at->diffForHumans() }}</flux:badge>
        @endif
    </div>

    <section class="space-y-3">
        <flux:heading size="lg">Access</flux:heading>
        <flux:text>Each line is a role in a place. A role for a whole business covers all of its divisions. If lines overlap, the strongest one wins.</flux:text>

        <div class="divide-y divide-zinc-200 dark:divide-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-700">
            @foreach ($this->lines as $line)
                <div class="flex items-center justify-between gap-2 p-3" wire:key="line-{{ $line->id }}">
                    <div>
                        <span class="font-medium">{{ $line->role?->name ?? 'Archived role' }}</span>
                        <span class="text-zinc-500">in</span> {{ $line->placeLabel() }}
                    </div>
                    @if ($this->canRemove($line))
                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="removeLine({{ $line->id }})"
                            wire:confirm="Remove {{ $line->role->name }} in {{ $line->placeLabel() }}?">Remove</flux:button>
                    @endif
                </div>
            @endforeach
        </div>
        <flux:error name="lines" />

        @can('edit-people')
            <form wire:submit="addLine" class="flex flex-col gap-2 md:flex-row md:items-end">
                <flux:select wire:model="roleId" label="Add a role" placeholder="Pick a role" class="md:w-56">
                    @foreach ($this->grantableRoles as $role)
                        <flux:select.option value="{{ $role->id }}">{{ $role->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="place" label="Where" placeholder="Pick where" class="md:flex-1">
                    @foreach ($this->places as $p)
                        <flux:select.option value="{{ $p['scope_type'] }}:{{ $p['scope_id'] }}">{{ str_repeat('— ', $p['depth']) }}{{ $p['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:button type="submit" icon="plus">Add</flux:button>
            </form>
            <flux:error name="roleId" />

            @if ($this->others->isNotEmpty())
                <form wire:submit="copyAccess" class="flex flex-col gap-2 md:flex-row md:items-end">
                    <flux:select wire:model="copyFromId" label="Or copy everything someone else has" placeholder="Pick a person" class="md:flex-1">
                        @foreach ($this->others as $other)
                            <flux:select.option value="{{ $other->id }}">{{ $other->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:button type="submit" icon="document-duplicate">Copy access</flux:button>
                </form>
                <flux:error name="copyFromId" />
            @endif
        @endcan
    </section>

    @can('view-directory')
        <section class="space-y-3">
            <flux:heading size="lg">Supervisor record</flux:heading>
            <flux:text>If this person is one of your supervisors, link them so "only their own work" can show the lots they supervise.</flux:text>
            <form wire:submit="linkSupervisor" class="flex flex-col gap-2 md:flex-row md:items-end">
                <flux:select wire:model="supervisorId" class="md:w-72" :disabled="! (Gate::allows('edit-people') && Gate::allows('edit-directory'))">
                    <flux:select.option value="">Not a supervisor</flux:select.option>
                    @foreach ($this->supervisors as $supervisor)
                        <flux:select.option value="{{ $supervisor->id }}">
                            {{ $supervisor->name }}{{ $supervisor->user_id && $supervisor->user_id !== $person->id ? ' (linked to someone else)' : '' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                @if (Gate::allows('edit-people') && Gate::allows('edit-directory'))
                    <flux:button type="submit">Save link</flux:button>
                @endif
            </form>
        </section>
    @endcan

    @can('full-people')
        <section class="space-y-3">
            <flux:heading size="lg">Account</flux:heading>
            @if ($person->isDeactivated())
                <flux:text>This account is closed: they can't sign in. Everything they did is kept.</flux:text>
                <flux:button wire:click="reactivate">Open the account again</flux:button>
            @else
                <flux:text>Closing the account stops them signing in. Nothing they did is deleted, and you can open it again later.</flux:text>
                @if ($this->worksElsewhere)
                    <flux:text>They also work for another organization, so remove their access lines here instead of closing the account.</flux:text>
                @endif
                <flux:button variant="danger" wire:click="deactivate" wire:confirm="Close {{ $person->name }}'s account?">Close account</flux:button>
            @endif
        </section>
    @endcan
</x-settings.layout>
