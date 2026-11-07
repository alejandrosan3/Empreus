<?php

namespace App\Livewire\Settings;

use App\Models\Organization;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Settings › Organization: the organization's name and how money is shown.
 */
#[Layout('layouts.app')]
class OrganizationSettings extends Component
{
    public Organization $organization;

    public string $name = '';

    public string $currencySymbol = '$';

    public function mount(): void
    {
        $this->organization = tenancy()->organization();
        $this->name = $this->organization->name;
        $this->currencySymbol = $this->organization->setting('currency_symbol');
    }

    public function save(): void
    {
        $this->authorize('edit-settings');
        abort_unless($this->canChange(), 403, 'Only people with access to the whole organization can change its settings.');

        $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('organizations', 'name')->ignore($this->organization->id)],
            'currencySymbol' => 'required|string|max:5',
        ]);

        $this->organization->update([
            'name' => trim($this->name),
            'settings' => array_merge($this->organization->settings ?? [], ['currency_symbol' => $this->currencySymbol]),
        ]);

        Flux::toast(text: 'Saved.', variant: 'success');
    }

    /** Organization-wide settings need Edit in Settings from an organization-level line (or Owner / platform admin). */
    public function canChange(): bool
    {
        return app(\App\Services\StructureService::class)->canChangeOrganization(auth()->user(), $this->organization);
    }

    public function render()
    {
        return view('livewire.settings.organization-settings');
    }
}
