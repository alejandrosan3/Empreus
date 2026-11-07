<?php

namespace App\Livewire\Settings;

use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Settings › My preferences: personal choices that only change the app for you.
 */
#[Layout('layouts.app')]
class Preferences extends Component
{
    public int $rowsPerPage = 20;

    public function mount(): void
    {
        $this->rowsPerPage = auth()->user()->rowsPerPage();
    }

    public function save(): void
    {
        $this->validate(['rowsPerPage' => ['required', 'integer', Rule::in(config('organization.rows_per_page_options'))]]);

        $user = auth()->user();
        $user->forceFill(['preferences' => array_merge($user->preferences ?? [], ['rows_per_page' => $this->rowsPerPage])])->save();

        Flux::toast(text: 'Saved.', variant: 'success');
    }

    public function render()
    {
        return view('livewire.settings.preferences');
    }
}
