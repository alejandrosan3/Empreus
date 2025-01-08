<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div> 

    <flux:navbar class="flex justify-between">
        <!-- "Add new element" button -->
        <flux:tooltip content="Add new X">
            <flux:button icon="plus"></flux:button>
        </flux:tooltip>

        <!-- "Filters" button -->
        <flux:tooltip content="Filters">
            <flux:modal.trigger name="filters">
                <flux:button  icon="adjustments-horizontal"></flux:button>
            </flux:modal.trigger>

            <flux:modal name="filters" class="md:w-96 space-y-6">
                <div>
                    <flux:heading size="lg">Filters</flux:heading>
                    <flux:subheading>Set the desired filter. Leave blank to ignore.</flux:subheading>
                </div>

                <flux:input label="Name" placeholder="Your name" />

                <flux:input label="Date of birth" type="date" />

                <div class="flex">
                    <flux:spacer />

                    <flux:button type="submit" variant="primary">Save changes</flux:button>
                </div>
            </flux:modal>
        </flux:tooltip>

        <!-- "Search" Input -->
        <flux:input placeholder="Search..." icon="magnifying-glass" class="mx-8" />

        <!-- "Rearrange, Edit and Freeze columns" button -->
        <livewire:layout.edit-columns-dropdown />

        
        <!-- Profile section -->
        <div class="flex ml-auto">
            <flux:separator vertical class="mr-6" />
            <flux:dropdown position="top" align="start">
                <flux:profile avatar="https://fluxui.dev/img/demo/user.png" />

                <flux:menu>
                    <flux:menu.item icon="user-circle" :href="route('profile')">Profile</flux:menu.item>

                    <flux:menu.separator />

                    <flux:menu.item wire:click="logout" icon="arrow-right-start-on-rectangle">Logout</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
        
    </flux:navbar>


</div>
