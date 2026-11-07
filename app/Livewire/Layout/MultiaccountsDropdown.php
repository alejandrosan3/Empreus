<?php

namespace App\Livewire\Layout;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Session;
use Livewire\Component;

/**
 * The division switcher: every division this person has access to, labelled
 * "Business › Division" (with the organization in front when they work for several).
 */
class MultiaccountsDropdown extends Component
{
    /** @var array<int, array{id: int, label: string}> */
    public array $accounts = [];

    #[Session(key: 'company_id')]
    public $selectedAccountId;

    public function mount(): void
    {
        $divisions = Auth::user()->accessibleDivisions()->with('business.organization')->get();
        $severalOrganizations = $divisions->pluck('business.organization_id')->unique()->count() > 1;

        $this->accounts = $divisions
            ->map(fn (Company $d) => [
                'id' => $d->id,
                'label' => ($severalOrganizations ? $d->business?->organization?->name.' · ' : '').$d->fullName(),
            ])
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.layout.multiaccounts-dropdown');
    }

    public function updatedSelectedAccountId($value)
    {
        $division = Company::find($value);
        if (! $division || ! Auth::user()->canAccessDivision($division)) {
            $this->selectedAccountId = tenancy()->divisionId();

            return null;
        }

        $this->dispatch('company-updated', $value);

        // Reload the current page so everything on it (including Settings) shows the new division.
        return $this->redirect(request()->header('Referer') ?: route('dashboard'), navigate: true);
    }
}
