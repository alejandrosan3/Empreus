<?php

namespace App\Livewire\Settings;

use App\Models\Role;
use App\Services\RoleService;
use Flux\Flux;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Settings › Roles: the organization's roles, archived roles and the shared library.
 */
#[Layout('layouts.app')]
class Roles extends Component
{
    public bool $showArchived = false;

    public string $newName = '';

    /** '' (blank role), a role id of this organization, or "library:<id>". */
    public string $startFrom = '';

    #[Computed]
    public function roles(): Collection
    {
        return Role::where('organization_id', tenancy()->organizationId())
            ->withCount('assignments')
            ->orderByDesc('is_owner')->orderBy('name')
            ->get();
    }

    #[Computed]
    public function archived(): Collection
    {
        return Role::onlyTrashed()->where('organization_id', tenancy()->organizationId())->orderBy('name')->get();
    }

    #[Computed]
    public function library(): Collection
    {
        return Role::library()->orderBy('name')->get();
    }

    public function createRole(RoleService $service)
    {
        $this->authorize('edit-people');
        $this->validate(['newName' => 'required|string|max:100'], ['newName.required' => 'Give the role a name.']);

        $source = match (true) {
            str_starts_with($this->startFrom, 'library:') => Role::library()->find((int) substr($this->startFrom, 8)),
            $this->startFrom !== '' => Role::where('organization_id', tenancy()->organizationId())->find((int) $this->startFrom),
            default => null,
        };

        try {
            $role = $service->create(auth()->user(), tenancy()->organization(), $this->newName, $source);
        } catch (InvalidArgumentException $e) {
            $this->addError('newName', $e->getMessage());

            return null;
        }

        return $this->redirectRoute('settings.roles.edit', $role, navigate: true);
    }

    public function useTemplate(int $templateId): void
    {
        $this->startFrom = 'library:'.$templateId;
        $this->newName = Role::library()->find($templateId)?->name ?? '';
        Flux::modal('new-role')->show();
    }

    public function archive(int $roleId, RoleService $service): void
    {
        $this->authorize('full-people');
        $this->run(fn () => $service->archive(auth()->user(), $this->findRole($roleId)), 'Role archived.');
    }

    public function restore(int $roleId, RoleService $service): void
    {
        $this->authorize('full-people');
        $role = Role::onlyTrashed()->where('organization_id', tenancy()->organizationId())->findOrFail($roleId);
        $this->run(fn () => $service->restore(auth()->user(), $role), 'Role restored.');
    }

    public function saveToLibrary(int $roleId, RoleService $service): void
    {
        $this->authorize('platform');
        $this->run(fn () => $service->saveToLibrary(auth()->user(), $this->findRole($roleId)), 'Saved to the library. Every organization can now copy it.');
    }

    public function removeFromLibrary(int $templateId, RoleService $service): void
    {
        $this->authorize('platform');
        $this->run(fn () => $service->removeFromLibrary(auth()->user(), Role::library()->findOrFail($templateId)), 'Removed from the library.');
    }

    private function findRole(int $id): Role
    {
        return Role::where('organization_id', tenancy()->organizationId())->findOrFail($id);
    }

    private function run(callable $action, string $done): void
    {
        try {
            $action();
            Flux::toast(text: $done, variant: 'success');
        } catch (InvalidArgumentException $e) {
            Flux::toast(text: $e->getMessage(), heading: 'Not done', variant: 'warning');
        }
        unset($this->roles, $this->archived, $this->library);
    }

    public function render()
    {
        return view('livewire.settings.roles');
    }
}
