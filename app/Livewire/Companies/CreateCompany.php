<?php

namespace App\Livewire\Companies;

use App\Models\Company;
use App\Models\User;
use Livewire\Component;
use Flux\Flux;

class CreateCompany extends Component
{
    public ?string $name = '';
    public ?string $description = null;

    protected function rules(): array
    {
        // Reuse existing validation rules
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function save(): void
    {
        $this->authorize('edit-settings');
        $data = $this->validate();
        try {
            // New divisions go in the current business. People with access to the
            // whole organization or business see it right away; nobody else is added.
            $company = Company::create([
                'business_id' => tenancy()->businessId(),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            Flux::toast(
                heading: 'All Done!',
                text: "Company {$company->name} was created successfully.",
                variant: 'success',
            );

            // Reset fields for a fresh form
            $this->reset(['name', 'description']);
        } catch (\Throwable $e) {
            Flux::toast(heading: 'oh, oh!', text: 'The company could not be created, please contact a platform admin and give them this error:'.$e->getMessage(), variant: 'danger',);
        }
    }


    public function render()
    {
        return view('livewire.companies.create-company')->layout('layouts.app');
    }
}
