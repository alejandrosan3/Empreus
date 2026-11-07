<x-settings.layout :heading="$role->name" subheading="Pick what this role can see and do in each part of the app.">
    <div>
        <flux:button size="sm" variant="ghost" icon="arrow-left" :href="route('settings.roles')" wire:navigate>All roles</flux:button>
    </div>

    {{-- Plain-language summary, updated as you change things --}}
    <div class="rounded-lg bg-zinc-100 p-4 dark:bg-zinc-800" aria-live="polite">
        <flux:heading>In plain words</flux:heading>
        <flux:text class="mt-1" data-test="summary">{{ $this->summary }}</flux:text>
    </div>

    @if ($role->is_owner)
        <flux:text>The Owner role always has every permission, so nobody can lock the organization out. Give it only to the people who run the organization.</flux:text>
    @endif

    <form wire:submit="save" class="space-y-8">
        <fieldset @disabled(! $this->canEdit) class="space-y-4">
            <flux:input wire:model.live.debounce.400ms="name" label="Name" />
            <flux:input wire:model="description" label="Description" placeholder="Who is this role for?" />
        </fieldset>

        <section>
            <flux:heading size="lg">What they can do</flux:heading>
            <flux:text class="mb-3">
                Hidden: not in the menu. View: can look, no edit buttons. Edit: can add and change. Full: edit, plus archive, restore and cancel.
            </flux:text>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-700">
                @foreach (config('permissions.areas') as $area => $info)
                    <div class="flex flex-col gap-2 p-3 md:flex-row md:items-center md:justify-between" wire:key="area-{{ $area }}">
                        <div class="min-w-0">
                            <div class="font-medium">{{ $info['label'] }}</div>
                            <flux:text size="sm">{{ $info['description'] }}</flux:text>
                        </div>
                        <flux:radio.group wire:model.live="levels.{{ $area }}" variant="segmented" size="sm" :disabled="! $this->canEdit">
                            @foreach (config('permissions.levels') as $level => $levelInfo)
                                <flux:radio value="{{ $level }}" label="{{ $levelInfo['label'] }}" />
                            @endforeach
                        </flux:radio.group>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="space-y-3">
            <flux:heading size="lg">Sensitive details</flux:heading>
            <flux:text>Even where they can see a page, these parts stay hidden unless switched on.</flux:text>
            @foreach (config('permissions.details') as $detail => $info)
                <flux:switch wire:model.live="details.{{ $detail }}" :label="$info['label']" :description="$info['description']" :disabled="! $this->canEdit" />
            @endforeach
        </section>

        <section class="space-y-3">
            <flux:heading size="lg">Which records</flux:heading>
            <flux:radio.group wire:model.live="reach" :disabled="! $this->canEdit">
                @foreach (config('permissions.reaches') as $key => $info)
                    <flux:radio value="{{ $key }}" :label="$info['label']" :description="$info['description'] ?? null" />
                @endforeach
            </flux:radio.group>
            <flux:switch wire:model="assignable" label="Can be given tasks" description="People with this role show up when assigning workers and building teams." :disabled="! $this->canEdit" />
        </section>

        @if ($this->tooMuch)
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
                <div class="font-medium">You can't give more access than you have:</div>
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($this->tooMuch as $problem)
                        <li>{{ $problem }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <flux:error name="levels" />

        <div class="flex flex-wrap gap-2">
            @if ($this->canEdit)
                <flux:button type="submit" variant="primary">Save role</flux:button>
            @endif
            @can('edit-people')
                <flux:button type="button" icon="eye" wire:click="viewAs">View the app as this role</flux:button>
            @endcan
        </div>
    </form>

    <section>
        <flux:heading size="lg">Who has it</flux:heading>
        @forelse ($this->holders as $line)
            <div class="flex items-center justify-between border-b border-zinc-200 py-2 dark:border-zinc-700" wire:key="holder-{{ $line->id }}">
                @can('view-people')
                    <a href="{{ route('settings.people.show', $line->user) }}" wire:navigate class="hover:underline">{{ $line->user->name }}</a>
                @endcan
                <flux:text size="sm">{{ $line->placeLabel() }}</flux:text>
            </div>
        @empty
            <flux:text>Nobody yet. Give this role to someone from the People page.</flux:text>
        @endforelse
    </section>
</x-settings.layout>
