<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Close (deactivate) the current account. Nothing is deleted: tasks, notes and
     * history keep their author, and an admin can reactivate the account later.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $user = Auth::user();
        foreach ($user->roleAssignments()->where('scope_type', \App\Models\RoleAssignment::ORGANIZATION)->whereHas('role', fn ($q) => $q->where('is_owner', true))->get() as $line) {
            $otherOwners = \App\Models\RoleAssignment::where('scope_type', \App\Models\RoleAssignment::ORGANIZATION)
                ->where('scope_id', $line->scope_id)
                ->where('user_id', '!=', $user->id)
                ->whereHas('role', fn ($q) => $q->where('is_owner', true))
                ->whereHas('user', fn ($q) => $q->whereNull('deactivated_at'))
                ->exists();

            if (! $otherOwners) {
                $this->addError('password', 'You are the only Owner of your organization. Make someone else Owner before closing your account.');

                return;
            }
        }

        app(\App\Services\PeopleService::class)->deactivate($user);
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6">
    <header>
            <flux:heading size="xl" level="2"> {{ __('Close Account') }}</flux:heading>

        <p class="mt-1 text-sm">
            {{ __("Closing your account logs you out and stops you from logging in again. Your work stays in place, and an admin can reopen the account later.") }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Close Account') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6">

            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Are you sure you want to close your account?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ __('Enter your password to confirm. Your work stays in place and an admin can reopen the account.') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('Password') }}" class="sr-only" />

                <x-text-input
                    wire:model="password"
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Password') }}"
                />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Close Account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
