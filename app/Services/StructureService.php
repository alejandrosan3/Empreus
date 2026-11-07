<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Company;
use App\Models\Organization;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use InvalidArgumentException;

/**
 * Adding, renaming and archiving businesses and divisions.
 *
 * Who may do it depends on where their "Settings" access comes from: a line for
 * the whole organization covers everything, a line for one business covers that
 * business and its divisions, a line for one division covers only that division.
 * Nothing is ever deleted; archived places keep all their records and can be restored.
 */
class StructureService
{
    /** Highest Settings level someone has over a place, from lines at or above it. */
    public function settingsLevelOver(User $user, Organization|Business|Company $place): int
    {
        if ($user->isPlatformAdmin()) {
            return Role::levelToValue('full');
        }
        if ($user->isDeactivated()) {
            return 0;
        }

        [$organizationId, $businessId, $divisionId] = match (true) {
            $place instanceof Organization => [$place->id, null, null],
            $place instanceof Business => [$place->organization_id, $place->id, null],
            $place instanceof Company => [Business::withTrashed()->whereKey($place->business_id)->value('organization_id'), $place->business_id, $place->id],
        };

        $level = 0;
        foreach ($user->roleAssignments()->with('role')->get() as $line) {
            $covers = match ($line->scope_type) {
                RoleAssignment::ORGANIZATION => $line->scope_id === $organizationId,
                RoleAssignment::BUSINESS => $businessId !== null && $line->scope_id === $businessId,
                RoleAssignment::DIVISION => $divisionId !== null && $line->scope_id === $divisionId,
                default => false,
            };
            if ($covers && $line->role) {
                $level = max($level, $line->role->levelValue('settings'));
            }
        }

        return $level;
    }

    public function can(User $user, Organization|Business|Company $place, string $level): bool
    {
        return $this->settingsLevelOver($user, $place) >= Role::levelToValue($level);
    }

    public function canChangeOrganization(User $user, Organization $organization): bool
    {
        return $this->can($user, $organization, 'edit');
    }

    public function addBusiness(User $user, Organization $organization, string $name): Business
    {
        $this->check($this->can($user, $organization, 'edit'), 'Adding a business needs Settings access for the whole organization.');
        $this->checkName(Business::withTrashed()->where('organization_id', $organization->id), $name);

        return Business::create(['organization_id' => $organization->id, 'name' => trim($name)]);
    }

    public function renameBusiness(User $user, Business $business, string $name): void
    {
        $this->check($this->can($user, $business, 'edit'), "You can't rename this business.");
        $this->checkName(Business::withTrashed()->where('organization_id', $business->organization_id)->whereKeyNot($business->id), $name);
        $business->update(['name' => trim($name)]);
    }

    public function archiveBusiness(User $user, Business $business): void
    {
        $this->check($this->can($user, $business->organization, 'full'), 'Archiving a business needs Full Settings access for the whole organization.');
        $this->check($business->divisions()->doesntExist(), 'Archive its divisions first.');
        $business->delete();
    }

    public function restoreBusiness(User $user, Business $business): void
    {
        $this->check($this->can($user, $business->organization, 'full'), "You can't restore businesses.");
        $business->restore();
    }

    public function addDivision(User $user, Business $business, string $name): Company
    {
        $this->check($this->can($user, $business, 'edit'), 'Adding a division needs Settings access for the whole business.');
        $this->checkName(Company::withTrashed()->where('business_id', $business->id), $name);

        return Company::create(['business_id' => $business->id, 'name' => trim($name)]);
    }

    public function renameDivision(User $user, Company $division, string $name): void
    {
        $this->check($this->can($user, $division, 'edit'), "You can't rename this division.");
        $this->checkName(Company::withTrashed()->where('business_id', $division->business_id)->whereKeyNot($division->id), $name);
        $division->update(['name' => trim($name)]);
    }

    public function archiveDivision(User $user, Company $division): void
    {
        $business = Business::withTrashed()->findOrFail($division->business_id);
        $this->check($this->can($user, $business, 'full'), 'Archiving a division needs Full Settings access for its business.');

        $left = Company::whereHas('business', fn ($q) => $q->where('organization_id', $business->organization_id))->count();
        $this->check($left > 1, "This is the organization's only division, so it can't be archived.");

        $division->delete();
    }

    public function restoreDivision(User $user, Company $division): void
    {
        $business = Business::withTrashed()->findOrFail($division->business_id);
        $this->check($this->can($user, $business, 'full'), "You can't restore divisions here.");
        $this->check(! $business->trashed(), 'Restore its business first.');
        $division->restore();
    }

    private function check(bool $ok, string $message): void
    {
        if (! $ok) {
            throw new InvalidArgumentException($message);
        }
    }

    private function checkName($siblings, string $name): void
    {
        $name = trim($name);
        $this->check($name !== '', 'Give it a name.');
        $this->check(! $siblings->where('name', $name)->exists(), "There's already one called \"$name\".");
    }
}
