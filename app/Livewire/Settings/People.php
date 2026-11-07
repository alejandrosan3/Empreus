<?php

namespace App\Livewire\Settings;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Services\PeopleService;
use App\Services\RoleService;
use Flux\Flux;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Settings › People: everyone with access somewhere in this organization.
 */
#[Layout('layouts.app')]
class People extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: false)]
    public bool $showDeactivated = false;

    // Invite form
    public string $name = '';

    public string $email = '';

    public ?int $roleId = null;

    /** "division:5", "business:2" or "organization:1" */
    public string $place = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedShowDeactivated(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function people()
    {
        $organizationId = tenancy()->organizationId();

        return User::query()
            ->whereHas('roleAssignments', fn ($q) => $q->inOrganization($organizationId))
            ->with(['roleAssignments' => fn ($q) => $q->inOrganization($organizationId)->with('role')])
            ->when(! $this->showDeactivated, fn ($q) => $q->whereNull('deactivated_at'))
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('email', 'like', '%'.$this->search.'%')))
            ->orderBy('name')
            ->paginate(25);
    }

    /** Roles this person may hand out. */
    #[Computed]
    public function grantableRoles(): Collection
    {
        $service = app(RoleService::class);

        return Role::where('organization_id', tenancy()->organizationId())
            ->orderByDesc('is_owner')->orderBy('name')->get()
            ->filter(fn (Role $role) => $service->canGrant(auth()->user(), $role))
            ->values();
    }

    #[Computed]
    public function places(): array
    {
        return app(PeopleService::class)->placesManagedBy(auth()->user(), tenancy()->organization());
    }

    public function openInvite(): void
    {
        $this->reset(['name', 'email', 'roleId']);
        $this->resetValidation();
        // Default to the division being worked in, when allowed.
        $here = RoleAssignment::DIVISION.':'.tenancy()->divisionId();
        $keys = array_map(fn ($p) => $p['scope_type'].':'.$p['scope_id'], $this->places);
        $this->place = in_array($here, $keys, true) ? $here : ($keys[0] ?? '');
        Flux::modal('invite')->show();
    }

    public function invite(PeopleService $people)
    {
        $this->authorize('edit-people');
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'roleId' => 'required|integer',
            'place' => 'required|string',
        ], [
            'roleId.required' => 'Pick a role.',
            'place.required' => 'Pick where.',
        ]);

        $role = Role::where('organization_id', tenancy()->organizationId())->find($this->roleId);
        [$scopeType, $scopeId] = array_pad(explode(':', $this->place, 2), 2, 0);

        try {
            if (! $role || ! app(RoleService::class)->canGrant(auth()->user(), $role)) {
                throw new InvalidArgumentException("You can't give that role.");
            }
            if (! $people->canManagePlace(auth()->user(), $scopeType, (int) $scopeId)) {
                throw new InvalidArgumentException("You can't give access there.");
            }

            $existed = User::where('email', $this->email)->exists();
            $user = $people->invite($this->name, $this->email, $role, [['scope_type' => $scopeType, 'scope_id' => (int) $scopeId]], auth()->user());
        } catch (InvalidArgumentException $e) {
            $this->addError('roleId', $e->getMessage());

            return null;
        }

        Flux::modal('invite')->close();
        Flux::toast(
            text: $existed
                ? "{$user->name} already had an account, so they now also have this access. No email was sent."
                : "We emailed {$user->email} a link to choose a password.",
            heading: 'Invited',
            variant: 'success',
        );

        return $this->redirectRoute('settings.people.show', $user, navigate: true);
    }

    public function render()
    {
        return view('livewire.settings.people');
    }
}
