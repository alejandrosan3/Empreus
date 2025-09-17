<div>

    <flux:heading size="xl" class="mb-8">Add Company </flux:heading>

    <flux:card class="space-y-6 max-w-lg">
        <form wire:submit.prevent="save" class="space-y-4">

        <flux:input wire:model.defer="name" id="name" label="Name" placeholder="Company name goes here" badge="Required" autofocus autocomplete="name"/>
        <flux:textarea wire:model.defer="description" id="description" label="Description" rows="auto" placeholder="Company description goes here" />


        <div class="pt-2">
            <flux:button type="submit" variant="primary">
                Create Company
            </flux:button>
        </div>
    </form>
    </flux:card>
</div>
