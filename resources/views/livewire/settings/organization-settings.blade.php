<x-settings.layout heading="Organization" subheading="Settings that apply to every business and division.">
    <form wire:submit="save" class="max-w-md space-y-6">
        <fieldset @disabled(! $this->canChange()) class="space-y-6">
            <flux:input wire:model="name" label="Organization name" />
            <flux:input wire:model="currencySymbol" label="Currency symbol" description="Shown in front of prices, for example $ or €." class="max-w-32" />
        </fieldset>

        @if ($this->canChange())
            <flux:button type="submit" variant="primary">Save</flux:button>
        @else
            <flux:text>Only people with access to the whole organization can change these.</flux:text>
        @endif
    </form>
</x-settings.layout>
