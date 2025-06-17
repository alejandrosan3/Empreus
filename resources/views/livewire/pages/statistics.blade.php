<div>
    <flux:card class="mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <flux:date-picker label="Start" with-inputs wire:model="startDate" />
            <flux:date-picker label="End" with-inputs wire:model="endDate" />
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

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <flux:card>
            <flux:heading size="md">Completed Lots</flux:heading>
            <span class="text-2xl">{{ $statistics['completed'] }}</span>
        </flux:card>
        <flux:card>
            <flux:heading size="md">Open Lots</flux:heading>
            <span class="text-2xl">{{ $statistics['open'] }}</span>
        </flux:card>
        <flux:card>
            <flux:heading size="md">Lots in Range</flux:heading>
            <span class="text-2xl">{{ $statistics['total'] }}</span>
        </flux:card>
        <flux:card>
            <flux:heading size="md">Tasks Finished</flux:heading>
            <span class="text-2xl">{{ $statistics['tasks_finished'] }}</span>
        </flux:card>
    </div>

    <flux:card class="mt-4">
        <flux:chart type="bar" :value="@js($statistics['chart'])" class="w-full h-64" />
    </flux:card>
</div>
