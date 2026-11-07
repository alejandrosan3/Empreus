<x-settings.layout heading="My preferences" subheading="These only change the app for you.">
    <form wire:submit="save" class="max-w-md space-y-6">
        <flux:select wire:model="rowsPerPage" label="Rows per page" description="How many rows lists like Lots, Tasks, Builders and People show at once.">
            @foreach (config('organization.rows_per_page_options') as $option)
                <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:text>Your name, email and password are on your <flux:link :href="route('profile')" wire:navigate>profile</flux:link>.</flux:text>

        <flux:button type="submit" variant="primary">Save</flux:button>
    </form>
</x-settings.layout>
