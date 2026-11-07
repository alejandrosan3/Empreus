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
 * Settings › Roles › one role: a level per area, the sensitive-details switches,
 * reach, and a live plain-language summary.
 */
#[Layout('layouts.app')]
class RoleEditor extends Component
{
    public Role $role;

    public string $name = '';

    public ?string $description = null;

    /** @var array<string, string> area => hidden|view|edit|full */
    public array $levels = [];

    /** @var array<string, bool> */
    public array $details = [];

    public string $reach = 'all';

    public bool $assignable = false;

    public function mount(Role $role): void
    {
        abort_unless($role->organization_id === tenancy()->organizationId(), 404);

        $this->role = $role;
        $this->name = $role->name;
        $this->description = $role->description;
        $this->levels = collect(config('permissions.areas'))->keys()->mapWithKeys(fn ($a) => [$a => $role->level($a)])->all();
        $this->details = collect(config('permissions.details'))->keys()->mapWithKeys(fn ($d) => [$d => $role->sees($d)])->all();
        $this->reach = $role->reach ?? 'all';
        $this->assignable = (bool) $role->assignable;
    }

    /** Whether this person can change the role at all. */
    #[Computed]
    public function canEdit(): bool
    {
        return ! $this->role->is_owner && auth()->user()->can('edit-people');
    }

    /** The role as it would be with the unsaved changes. */
    private function draft(): Role
    {
        $draft = $this->role->replicate();
        $draft->forceFill([
            'name' => $this->name,
            'levels' => $this->levels,
            'details' => $this->details,
            'reach' => $this->reach,
        ]);
        $draft->setRelation('organization', $this->role->organization);

        return $draft;
    }

    #[Computed]
    public function summary(): string
    {
        return $this->draft()->summary();
    }

    /** Reasons the unsaved role is more than this person may give. */
    #[Computed]
    public function tooMuch(): array
    {
        return $this->canEdit ? app(RoleService::class)->whatExceeds(auth()->user(), $this->draft()) : [];
    }

    #[Computed]
    public function holders(): Collection
    {
        return $this->role->assignments()->with('user')->get()->sortBy('user.name')->values();
    }

    public function save(RoleService $service): void
    {
        $this->authorize('edit-people');
        $this->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $service->update(auth()->user(), $this->role, [
                'name' => $this->name,
                'description' => $this->description,
                'levels' => $this->levels,
                'details' => $this->details,
                'reach' => $this->reach,
                'assignable' => $this->assignable,
            ]);
            Flux::toast(text: 'Saved. People with this role see the change on their next click.', heading: $this->role->name, variant: 'success');
        } catch (InvalidArgumentException $e) {
            $this->addError('levels', $e->getMessage());
        }
    }

    /** Preview the app as this role. The person's own access can only go down, never up. */
    public function viewAs()
    {
        $this->authorize('edit-people');
        session(['view_as_role_id' => $this->role->id]);

        return $this->redirectRoute('dashboard');
    }

    public function render()
    {
        return view('livewire.settings.role-editor');
    }
}
