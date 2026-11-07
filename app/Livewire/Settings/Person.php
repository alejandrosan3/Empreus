<?php

namespace App\Livewire\Settings;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\Supervisor;
use App\Models\User;
use App\Services\PeopleService;
use App\Services\RoleService;
use Flux\Flux;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Settings › People › one person: their access lines in this organization,
 * copying access from someone else, closing the account and the supervisor link.
 */
#[Layout('layouts.app')]
class Person extends Component
{
    public User $person;

    // "Add access" form
    public ?int $roleId = null;

    public string $place = '';

    public ?int $copyFromId = null;

    public ?int $supervisorId = null;

    public function mount(User $user): void
    {
        $inHere = $user->roleAssignments()->inOrganization(tenancy()->organizationId())->exists();
        abort_unless($inHere, 404);

        $this->person = $user;
        $this->supervisorId = Supervisor::where('user_id', $user->id)->value('id');
    }

    #[Computed]
    public function lines(): Collection
    {
        return $this->person->roleAssignments()
            ->inOrganization(tenancy()->organizationId())
            ->with('role')
            ->get();
    }

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

    #[Computed]
    public function others(): Collection
    {
        $organizationId = tenancy()->organizationId();

        return User::whereKeyNot($this->person->id)
            ->whereNull('deactivated_at')
            ->whereHas('roleAssignments', fn ($q) => $q->inOrganization($organizationId))
            ->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function supervisors(): Collection
    {
        return Supervisor::orderBy('name')->get(['id', 'name', 'user_id']);
    }

    /** Whether the person has access in other organizations (closing the account would affect those too). */
    #[Computed]
    public function worksElsewhere(): bool
    {
        return $this->person->roleAssignments()->count() > $this->lines->count();
    }

    public function canRemove(RoleAssignment $line): bool
    {
        $people = app(PeopleService::class);

        return auth()->user()->can('edit-people')
            && $line->role
            && app(RoleService::class)->canGrant(auth()->user(), $line->role)
            && $people->canManagePlace(auth()->user(), $line->scope_type, $line->scope_id);
    }

    public function addLine(PeopleService $people): void
    {
        $this->authorize('edit-people');
        $this->validate(['roleId' => 'required|integer', 'place' => 'required|string'], [
            'roleId.required' => 'Pick a role.', 'place.required' => 'Pick where.',
        ]);

        $role = Role::where('organization_id', tenancy()->organizationId())->find($this->roleId);
        [$scopeType, $scopeId] = array_pad(explode(':', $this->place, 2), 2, 0);

        $this->attempt(function () use ($people, $role, $scopeType, $scopeId) {
            if (! $role) {
                throw new InvalidArgumentException('Pick a role.');
            }
            $people->grantAs(auth()->user(), $this->person, $role, $scopeType, (int) $scopeId);
            $this->reset('roleId');
        }, 'Access added.', 'roleId');
    }

    public function removeLine(int $lineId, PeopleService $people): void
    {
        $this->authorize('edit-people');
        $line = $this->lines->firstWhere('id', $lineId);
        abort_unless($line, 404);

        $this->attempt(fn () => $people->revokeAs(auth()->user(), $line), 'Access removed.');
    }

    public function copyAccess(PeopleService $people): void
    {
        $this->authorize('edit-people');
        $from = $this->others->firstWhere('id', $this->copyFromId);
        abort_unless($from, 404);

        $this->attempt(function () use ($people, $from) {
            $copied = 0;
            foreach ($from->roleAssignments()->inOrganization(tenancy()->organizationId())->with('role')->get() as $line) {
                if ($line->role && ! $line->role->is_owner) {
                    $people->grantAs(auth()->user(), $this->person, $line->role, $line->scope_type, $line->scope_id);
                    $copied++;
                }
            }
            if ($copied === 0) {
                throw new InvalidArgumentException("{$from->name} has nothing that can be copied.");
            }
        }, "Copied {$from->name}'s access.", 'copyFromId');
    }

    public function linkSupervisor(): void
    {
        $this->authorize('edit-people');
        $this->authorize('edit-directory');

        Supervisor::where('user_id', $this->person->id)->update(['user_id' => null]);
        if ($this->supervisorId) {
            Supervisor::whereKey($this->supervisorId)->firstOrFail()->update(['user_id' => $this->person->id]);
        }
        unset($this->supervisors);
        Flux::toast(text: $this->supervisorId ? 'Linked. Their "own work" now includes this supervisor\'s lots.' : 'Unlinked.', variant: 'success');
    }

    public function deactivate(PeopleService $people): void
    {
        $this->authorize('full-people');

        $this->attempt(function () use ($people) {
            if ($this->person->is(auth()->user())) {
                throw new InvalidArgumentException("You can't close your own account here. Use your profile page.");
            }
            if ($this->worksElsewhere && ! auth()->user()->isPlatformAdmin()) {
                throw new InvalidArgumentException('They also work for another organization. Remove their access here instead.');
            }
            if ($this->person->isOwnerOf(tenancy()->organization())) {
                throw new InvalidArgumentException("Owners can't be closed here. Remove their Owner line first (someone else must be Owner).");
            }
            if (! app(RoleService::class)->isUnlimited(auth()->user(), tenancy()->organization())
                && $this->lines->contains(fn ($line) => ! $this->canRemove($line))) {
                throw new InvalidArgumentException('They have access you don\'t manage. Ask an Owner to close this account.');
            }
            $people->deactivate($this->person);
        }, "{$this->person->name} can no longer sign in. Their work history stays.");
    }

    public function reactivate(PeopleService $people): void
    {
        $this->authorize('full-people');
        $people->reactivate($this->person);
        Flux::toast(text: "{$this->person->name} can sign in again.", variant: 'success');
    }

    private function attempt(callable $action, string $done, string $errorField = 'lines'): void
    {
        try {
            $action();
            $this->person->refresh();
            unset($this->lines, $this->worksElsewhere);
            Flux::toast(text: $done, variant: 'success');
        } catch (InvalidArgumentException $e) {
            $this->addError($errorField, $e->getMessage());
            Flux::toast(text: $e->getMessage(), heading: 'Not done', variant: 'warning');
        }
    }

    public function render()
    {
        return view('livewire.settings.person');
    }
}
