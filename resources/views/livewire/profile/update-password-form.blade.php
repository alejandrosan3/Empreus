<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section>
    <header>
        <flux:heading size="xl" level="2">
            {{ __('Update Password') }}
        </flux:heading>

        <p class="mt-1 text-sm">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form wire:submit="updatePassword" class="mt-6 space-y-6">
        {{-- Current Password --}}
        <flux:input
            type="password"
            wire:model="current_password"
            id="update_password_current_password"
            name="current_password"
            label="{{ __('Current Password') }}"
            autocomplete="current-password"
        />

        {{-- New Password --}}
        <flux:input
            type="password"
            wire:model="password"
            id="update_password_password"
            name="password"
            label="{{ __('New Password') }}"
            autocomplete="new-password"
        />

        {{-- Confirm Password --}}
        <flux:input
            type="password"
            wire:model="password_confirmation"
            id="update_password_password_confirmation"
            name="password_confirmation"
            label="{{ __('Confirm Password') }}"
            autocomplete="new-password"
        />

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            <x-action-message class="me-3" on="password-updated">
                {{ __('Saved.') }}
            </x-action-message>
        </div>
    </form>
</section>
