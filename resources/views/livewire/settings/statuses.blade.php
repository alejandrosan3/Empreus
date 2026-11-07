<x-settings.layout heading="Statuses" :subheading="'Rename statuses and pick their colors for '.(tenancy()->business()?->name ?? 'this business').'. What each status does stays the same.'">
    <form wire:submit="save" class="space-y-4">
        <div class="divide-y divide-zinc-200 dark:divide-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-700">
            @foreach ($this->statusTypes as $status)
                <div class="flex flex-col gap-2 p-3 sm:flex-row sm:items-center" wire:key="status-{{ $status->identifier }}">
                    <div class="sm:w-40">
                        <flux:badge :color="$statuses[$status->identifier]['color'] ?? 'zinc'">{{ $statuses[$status->identifier]['name'] ?? '' }}</flux:badge>
                        @if (($statuses[$status->identifier]['name'] ?? '') !== $status->originalName())
                            <flux:text size="sm">Standard name: {{ $status->originalName() }}</flux:text>
                        @endif
                    </div>
                    <fieldset @disabled(! $this->canChange()) class="flex flex-1 flex-col gap-2 sm:flex-row">
                        <flux:input wire:model.live.debounce.300ms="statuses.{{ $status->identifier }}.name" aria-label="Name" class="sm:flex-1" />
                        <flux:select wire:model.live="statuses.{{ $status->identifier }}.color" aria-label="Color" class="sm:w-40">
                            @foreach (\App\Models\StatusType::COLORS as $color)
                                <flux:select.option value="{{ $color }}">{{ ucfirst($color) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </fieldset>
                </div>
                <flux:error name="statuses.{{ $status->identifier }}.name" />
            @endforeach
        </div>

        @if ($this->canChange())
            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">Save</flux:button>
                <flux:button type="button" variant="ghost" wire:click="resetToDefaults" wire:confirm="Go back to the standard names and colors?">Back to standard</flux:button>
            </div>
        @else
            <flux:text>Changing statuses needs Settings access for the whole business.</flux:text>
        @endif
    </form>
</x-settings.layout>
