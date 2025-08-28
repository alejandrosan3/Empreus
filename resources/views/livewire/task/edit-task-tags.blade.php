<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-center gap-2">
        <flux:heading size="md">Manage Tags</flux:heading>
        <flux:text size="sm" class="text-zinc-500">Attach tags and optionally set a time (e.g., 9:00 AM).</flux:text>
        <flux:spacer />
    </div>

    {{-- Add tag row --}}
    <div class="flex items-end gap-2">
        <div class="grow">
            <flux:select wire:model.live="tagToAdd" label="Add tag…" clearable searchable>
                @foreach($available as $t)
                    <flux:select.option value="{{ $t['id'] }}">
                        {{ $t['name'] }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @if($tagToAdd)
        <flux:button
            variant="primary"
            wire:click="addSelectedTag"
            title="Add selected tag"
        >
            Add
        </flux:button>
        @endif
    </div>

    {{-- Attached tags --}}
    <div class="space-y-2">
        <flux:heading size="sm">Attached</flux:heading>

        @forelse($attached as $t)
            @php
                $style = !empty($t['color'])
                    ? "background-color: {$t['color']}22; color: {$t['color']}"
                    : '';
            @endphp
            <div class="flex items-center gap-3 p-2 rounded-lg border border-zinc-200 dark:border-white/10 bg-white dark:bg-zinc-800">
                {{-- Tag label --}}
                <flux:badge size="sm" class="shrink-0" style="{{ $style }}">
                    {{ $t['name'] }}
                </flux:badge>

                {{-- Time picker (HH:MM) --}}
                <div class="grow">
                    <flux:input
                        type="time"
                        wire:change="updateTime({{ $t['id'] }}, $event.target.value)"
                        value="{{ $t['time'] }}"
                        label="Time of day"
                        placeholder="--:--"
                    />
                </div>

                {{-- Remove (icon-only) --}}
                <flux:button
                    variant="subtle"
                    color="red"
                    size="xs"
                    icon="trash"
                    wire:click="removeTag({{ $t['id'] }})"
                    title="Remove tag"
                    aria-label="Remove tag"
                />
            </div>
        @empty
            <div class="p-3 rounded-lg border border-dashed border-zinc-300 dark:border-white/10 text-zinc-500 dark:text-zinc-300">
                No tags attached yet. Use “Add tag…” to attach one.
            </div>
        @endforelse
    </div>

    <flux:text size="xs" class="text-zinc-500">
        Times are optional. Leaving time blank will show just the tag name.
    </flux:text>
</div>
