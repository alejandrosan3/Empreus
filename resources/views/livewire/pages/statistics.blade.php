<div>
    <flux:card class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <flux:date-picker mode="range" with-inputs wire:model.live="range" />
            <flux:select label="Preset" wire:model.defer="selectedPreset">
                <option value="">Select Preset</option>
                @foreach($presets as $key => $preset)
                    <flux:select.option value="{{ $key }}">{{ $preset['label'] }}</flux:select.option>
                @endforeach
            </flux:select>
            <div class="flex items-end">
                <flux:button size="sm" wire:click="applyPreset">Apply</flux:button>
            </div>
        </div>
    </flux:card>

    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
        <flux:card>
            <flux:heading size="lg">Completed Lots</flux:heading>
            <span class="text-2xl">{{ $statistics['completed'] }}</span>
        </flux:card>
        <flux:card>
            <flux:heading size="lg">Open Lots</flux:heading>
            <span class="text-2xl">{{ $statistics['open'] }}</span>
        </flux:card>
        <flux:card>
            <flux:heading size="lg">Lots in Range</flux:heading>
            <span class="text-2xl">{{ $statistics['total'] }}</span>
        </flux:card>
        <flux:card>
            <flux:heading size="lg">Tasks Finished</flux:heading>
            <span class="text-2xl">{{ $statistics['tasks_finished'] }}</span>
        </flux:card>
        <flux:card>
            <flux:heading size="lg">Completed Revenue</flux:heading>
            <span class="text-2xl">${{ number_format($statistics['completed_revenue'], 2) }}</span>
        </flux:card>
        <flux:card>
            <flux:heading size="lg">Projected Revenue</flux:heading>
            <span class="text-2xl">${{ number_format($statistics['projected_revenue'], 2) }}</span>
        </flux:card>
    </div>

    <flux:card class="mt-4">
        <flux:heading size="lg">Closed Lots by Subdivision</flux:heading>
        @foreach($statistics['subdivisions'] as $builder => $subdivisions)
            <h3 class="mt-4 font-bold">{{ $builder }}</h3>
            <flux:table class="mb-4">
                <flux:table.columns>
                    <flux:table.column>Subdivision</flux:table.column>
                    <flux:table.column>Closed Lots</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($subdivisions as $subdivision => $count)
                        <flux:table.row>
                            <flux:table.cell>{{ $subdivision }}</flux:table.cell>
                            <flux:table.cell>{{ $count }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endforeach
    </flux:card>
</div>
