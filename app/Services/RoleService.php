<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Creating, changing and archiving roles, and the rule that keeps it safe:
 * nobody can make or hand out a role that is stronger than their own access.
 *
 * Owners of the organization and platform admins are not limited.
 */
class RoleService
{
    /** Whether the actor has no limits in this organization. */
    public function isUnlimited(User $actor, Organization $organization): bool
    {
        return $actor->isPlatformAdmin() || $actor->isOwnerOf($organization);
    }

    /**
     * Whether a role (or unsaved settings for one) is within the actor's own access.
     * Returns the reasons it isn't, in plain language; an empty list means it's fine.
     *
     * @return array<int, string>
     */
    public function whatExceeds(User $actor, Role $role): array
    {
        $organization = $role->organization;
        if ($organization && $this->isUnlimited($actor, $organization)) {
            return [];
        }

        if ($role->is_owner) {
            return ['Only Owners can give the Owner role.'];
        }

        $problems = [];
        foreach (config('permissions.areas') as $area => $info) {
            if ($role->levelValue($area) > $actor->levelFor($area)) {
                $problems[] = "{$info['label']}: you have ".config('permissions.levels.'.Role::valueToLevel($actor->levelFor($area)).'.label').' yourself.';
            }
        }
        foreach (config('permissions.details') as $detail => $info) {
            if ($role->sees($detail) && ! $actor->sees($detail)) {
                $problems[] = "{$info['label']}: you can't see these yourself.";
            }
        }
        if ($role->reach === 'all' && $actor->reachIn() === 'own') {
            $problems[] = 'You only see your own work, so you can\'t give access to everything.';
        }

        return $problems;
    }

    public function canGrant(User $actor, Role $role): bool
    {
        return $this->whatExceeds($actor, $role) === [];
    }

    /**
     * Start a new role in an organization, as a copy of one of its roles or of a library template.
     */
    public function create(User $actor, Organization $organization, string $name, ?Role $startFrom = null): Role
    {
        if ($startFrom && ! $startFrom->isLibraryTemplate() && $startFrom->organization_id !== $organization->id) {
            throw new InvalidArgumentException("That role isn't in this organization.");
        }

        $source = $startFrom ?? new Role([
            'levels' => Role::expandLevels([]),
            'details' => array_fill_keys(array_keys(config('permissions.details')), false),
            'reach' => 'all',
            'assignable' => false,
        ]);

        // An Owner copy becomes a normal role with everything set to Full.
        $levels = $source->is_owner ? Role::expandLevels('*full') : Role::expandLevels($source->levels ?? []);
        $details = $source->is_owner ? array_fill_keys(array_keys(config('permissions.details')), true) : ($source->details ?? []);

        $draft = new Role([
            'organization_id' => $organization->id,
            'name' => $this->uniqueName($organization->id, trim($name)),
            'description' => $source->description,
            'levels' => $levels,
            'details' => $details,
            'reach' => $source->reach ?? 'all',
            'assignable' => (bool) $source->assignable,
            'is_owner' => false,
        ]);
        $draft->setRelation('organization', $organization);

        $this->assertWithin($actor, $draft);
        $draft->save();

        return $draft;
    }

    /**
     * Save changes from the role editor.
     *
     * @param  array{name?: string, description?: ?string, levels?: array, details?: array, reach?: string, assignable?: bool}  $changes
     */
    public function update(User $actor, Role $role, array $changes): Role
    {
        if ($role->is_owner) {
            throw new InvalidArgumentException("The Owner role always has every permission and can't be changed.");
        }

        $levels = array_keys(config('permissions.levels'));
        foreach ($changes['levels'] ?? [] as $area => $level) {
            if (! in_array($level, $levels, true)) {
                throw new InvalidArgumentException("Unknown level \"$level\".");
            }
        }
        if (isset($changes['reach']) && ! array_key_exists($changes['reach'], config('permissions.reaches'))) {
            throw new InvalidArgumentException('Unknown reach.');
        }

        $role->fill([
            'name' => isset($changes['name']) ? trim($changes['name']) : $role->name,
            'description' => array_key_exists('description', $changes) ? $changes['description'] : $role->description,
            'levels' => isset($changes['levels']) ? Role::expandLevels($changes['levels']) : $role->levels,
            'details' => isset($changes['details'])
                ? array_map('boolval', array_intersect_key($changes['details'], config('permissions.details')))
                : $role->details,
            'reach' => $changes['reach'] ?? $role->reach,
            'assignable' => (bool) ($changes['assignable'] ?? $role->assignable),
        ]);

        if ($role->isDirty('name') && Role::withTrashed()
            ->where('organization_id', $role->organization_id)
            ->where('name', $role->name)
            ->whereKeyNot($role->id)->exists()) {
            throw new InvalidArgumentException('Another role already has that name.');
        }

        $this->assertWithin($actor, $role);
        $role->save();

        return $role;
    }

    /**
     * Archive a role. Roles still given to people can't be archived, so nobody
     * silently loses access.
     */
    public function archive(User $actor, Role $role): void
    {
        if ($role->is_owner) {
            throw new InvalidArgumentException("The Owner role can't be archived.");
        }

        $this->assertWithin($actor, $role);

        $inUse = $role->assignments()->count();
        if ($inUse > 0) {
            throw new InvalidArgumentException(
                $inUse === 1
                    ? 'One person still has this role. Give them another role first.'
                    : "$inUse access lines still use this role. Give those people another role first."
            );
        }

        $role->delete();
    }

    public function restore(User $actor, Role $role): void
    {
        $this->assertWithin($actor, $role);
        $role->restore();
    }

    /** Platform admins can save an organization's role to the library for every organization to copy. */
    public function saveToLibrary(User $actor, Role $role, ?string $name = null): Role
    {
        if (! $actor->isPlatformAdmin()) {
            throw new InvalidArgumentException('Only platform admins can add roles to the library.');
        }

        $name = trim($name ?: $role->name);
        $base = $name;
        $n = 2;
        while (Role::library()->where('name', $name)->exists()) {
            $name = "$base ($n)";
            $n++;
        }

        return Role::create([
            'organization_id' => null,
            'key' => null,
            'name' => $name,
            'description' => $role->description,
            'levels' => $role->is_owner ? Role::expandLevels('*full') : Role::expandLevels($role->levels ?? []),
            'details' => $role->details,
            'reach' => $role->reach,
            'assignable' => $role->assignable,
            'is_owner' => false,
        ]);
    }

    public function removeFromLibrary(User $actor, Role $template): void
    {
        if (! $actor->isPlatformAdmin() || ! $template->isLibraryTemplate()) {
            throw new InvalidArgumentException('Only platform admins can remove library roles.');
        }

        $template->delete();
    }

    private function assertWithin(User $actor, Role $role): void
    {
        if ($problems = $this->whatExceeds($actor, $role)) {
            throw new InvalidArgumentException("You can't give more access than you have. ".implode(' ', $problems));
        }
    }

    private function uniqueName(int $organizationId, string $name): string
    {
        $name = $name !== '' ? $name : 'New role';
        $base = $name;
        $n = 2;
        while (Role::withTrashed()->where('organization_id', $organizationId)->where('name', $name)->exists()) {
            $name = "$base ($n)";
            $n++;
        }

        return $name;
    }
}
