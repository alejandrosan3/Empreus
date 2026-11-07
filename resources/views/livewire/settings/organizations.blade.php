<x-settings.layout heading="All organizations" subheading="Only platform admins see this page.">
    <div>
        <flux:modal.trigger name="new-organization">
            <flux:button variant="primary" icon="plus">New organization</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="divide-y divide-zinc-200 dark:divide-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-700">
        @foreach ($this->organizations as $organization)
            <div class="flex items-center justify-between gap-2 p-4" wire:key="org-{{ $organization->id }}">
                <div>
                    <div class="font-medium">{{ $organization->name }}</div>
                    <flux:text size="sm">
                        {{ trans_choice(':count business|:count businesses', $organization->businesses_count) }},
                        {{ trans_choice(':count division|:count divisions', $organization->divisions_count) }}
                        @if (tenancy()->organizationId() === $organization->id) · you're in this one now @endif
                    </flux:text>
                </div>
                <flux:button size="sm" wire:click="open({{ $organization->id }})">Open</flux:button>
            </div>
        @endforeach
    </div>

    <flux:modal name="new-organization" class="md:w-[28rem]">
        <form wire:submit="create" class="space-y-4">
            <flux:heading size="lg">New organization</flux:heading>
            <flux:text>A new customer account. It starts with the standard roles, one business and one division. The Owner gets an email to choose a password.</flux:text>
            <flux:input wire:model="organizationName" label="Organization name" placeholder="For example: Empreus" />
            <flux:input wire:model="businessName" label="First business (line of work)" placeholder="For example: Cleaning" />
            <flux:input wire:model="divisionName" label="First division (branch)" placeholder="For example: Jacksonville" />
            <flux:separator />
            <flux:input wire:model="ownerName" label="Owner's name" />
            <flux:input wire:model="ownerEmail" type="email" label="Owner's email" />
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">Create</flux:button>
            </div>
        </form>
    </flux:modal>
</x-settings.layout>
