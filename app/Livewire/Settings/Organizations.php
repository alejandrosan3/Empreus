<?php

namespace App\Livewire\Settings;

use App\Models\Business;
use App\Models\Company;
use App\Models\Organization;
use App\Services\PeopleService;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Platform › All organizations: only for platform admins. Lists every customer
 * and sets up a new one with its first business, division and Owner.
 */
#[Layout('layouts.app')]
class Organizations extends Component
{
    public string $organizationName = '';

    public string $businessName = '';

    public string $divisionName = '';

    public string $ownerName = '';

    public string $ownerEmail = '';

    #[Computed]
    public function organizations(): Collection
    {
        return Organization::withCount(['businesses', 'divisions'])->orderBy('name')->get();
    }

    public function create(PeopleService $people): void
    {
        $this->authorize('platform');
        $data = $this->validate([
            'organizationName' => 'required|string|max:255|unique:organizations,name',
            'businessName' => 'required|string|max:255',
            'divisionName' => 'required|string|max:255',
            'ownerName' => 'required|string|max:255',
            'ownerEmail' => 'required|email|max:255',
        ], [
            'organizationName.unique' => 'There is already an organization with that name.',
        ]);

        DB::transaction(function () use ($data, $people) {
            $organization = Organization::create(['name' => $data['organizationName']]);
            $business = Business::create(['organization_id' => $organization->id, 'name' => $data['businessName']]);
            Company::create(['business_id' => $business->id, 'name' => $data['divisionName']]);

            $owner = $organization->roles()->where('is_owner', true)->firstOrFail();
            $people->invite($data['ownerName'], $data['ownerEmail'], $owner, [
                ['scope_type' => 'organization', 'scope_id' => $organization->id],
            ], auth()->user());
        });

        $this->reset();
        unset($this->organizations);
        Flux::modal('new-organization')->close();
        Flux::toast(text: 'Organization created and the Owner was invited.', variant: 'success');
    }

    /** Jump into an organization to look around or help (platform admins can open any division). */
    public function open(int $organizationId)
    {
        $this->authorize('platform');
        $division = Company::whereHas('business', fn ($q) => $q->where('organization_id', $organizationId))->orderBy('name')->first();
        if (! $division) {
            Flux::toast(text: 'That organization has no divisions yet.', variant: 'warning');

            return null;
        }
        session(['company_id' => $division->id]);

        return $this->redirectRoute('settings', navigate: false);
    }

    public function render()
    {
        return view('livewire.settings.organizations');
    }
}
