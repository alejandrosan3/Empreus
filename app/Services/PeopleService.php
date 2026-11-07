<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Company;
use App\Models\Organization;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Notifications\Invitation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Adding people and changing who has access to what.
 */
class PeopleService
{
    /**
     * Create (or reuse) a person and give them a role in a place.
     *
     * New people get a random password and an email with a link to choose their own.
     *
     * @param  array<int, array{scope_type: string, scope_id: int}>  $places
     */
    public function invite(string $name, string $email, Role $role, array $places, ?User $invitedBy = null, bool $sendEmail = true): User
    {
        return DB::transaction(function () use ($name, $email, $role, $places, $invitedBy, $sendEmail) {
            $user = User::where('email', $email)->first();
            $isNew = ! $user;

            $user ??= User::create([
                'name' => $name,
                'email' => $email,
                'role' => $role->assignable ? 'worker' : 'admin',
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
            ]);

            foreach ($places as $place) {
                $this->grant($user, $role, $place['scope_type'], (int) $place['scope_id']);
            }

            if ($isNew && $sendEmail) {
                $user->notify(new Invitation(
                    Password::broker()->createToken($user),
                    $role->organization?->name ?? config('app.name'),
                    $invitedBy?->name,
                ));
            }

            return $user;
        });
    }

    /** Add one access line, e.g. "Scheduler in Cleaning › Jacksonville". */
    public function grant(User $user, Role $role, string $scopeType, int $scopeId): RoleAssignment
    {
        $this->assertPlaceMatchesRole($role, $scopeType, $scopeId);

        $assignment = RoleAssignment::firstOrCreate([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
        ]);
        $user->flushAccessCache();

        return $assignment;
    }

    /**
     * Give access on behalf of someone, checking that they may: the role is no
     * stronger than their own, and the place is one they manage.
     */
    public function grantAs(User $actor, User $user, Role $role, string $scopeType, int $scopeId): RoleAssignment
    {
        if (! app(RoleService::class)->canGrant($actor, $role)) {
            throw new InvalidArgumentException("You can't give the {$role->name} role: it has more access than you do.");
        }
        if (! $this->canManagePlace($actor, $scopeType, $scopeId)) {
            throw new InvalidArgumentException("You can't give access there.");
        }

        return $this->grant($user, $role, $scopeType, $scopeId);
    }

    /** Remove an access line on behalf of someone, checking the role and place like grantAs(). */
    public function revokeAs(User $actor, RoleAssignment $assignment): void
    {
        if (! $assignment->role || ! app(RoleService::class)->canGrant($actor, $assignment->role)
            || ! $this->canManagePlace($actor, $assignment->scope_type, $assignment->scope_id)) {
            throw new InvalidArgumentException("You can't remove that access line.");
        }

        $this->revoke($assignment);
    }

    /**
     * Whether someone can hand out access in a place: they need Edit in "People & roles"
     * from a line that covers that place. A Jacksonville admin can't give access to all
     * of Cleaning; a Cleaning admin can give access in any Cleaning division.
     */
    public function canManagePlace(User $actor, string $scopeType, int $scopeId): bool
    {
        $organizationId = $this->organizationIdOf($scopeType, $scopeId);
        if (! $organizationId) {
            return false;
        }
        if ($actor->isPlatformAdmin()) {
            return true;
        }
        if ($actor->isDeactivated()) {
            return false;
        }

        $businessId = match ($scopeType) {
            RoleAssignment::BUSINESS => $scopeId,
            RoleAssignment::DIVISION => Company::whereKey($scopeId)->value('business_id'),
            default => null,
        };
        $needed = Role::levelToValue('edit');

        return $actor->roleAssignments()->with('role')->get()->contains(function (RoleAssignment $line) use ($scopeType, $scopeId, $organizationId, $businessId, $needed) {
            if (! $line->role || $line->role->levelValue('people') < $needed) {
                return false;
            }

            return match ($line->scope_type) {
                RoleAssignment::ORGANIZATION => $line->scope_id === $organizationId,
                RoleAssignment::BUSINESS => $businessId !== null && $line->scope_id === $businessId,
                RoleAssignment::DIVISION => $scopeType === RoleAssignment::DIVISION && $line->scope_id === $scopeId,
                default => false,
            };
        });
    }

    /** Places in an organization where someone can hand out access (for pickers). */
    public function placesManagedBy(User $actor, Organization $organization): array
    {
        return array_values(array_filter(
            $this->placesIn($organization),
            fn ($place) => $this->canManagePlace($actor, $place['scope_type'], $place['scope_id'])
        ));
    }

    /** Remove one access line. The last Owner of an organization can't be removed. */
    public function revoke(RoleAssignment $assignment): void
    {
        if ($assignment->role?->is_owner && $assignment->scope_type === RoleAssignment::ORGANIZATION) {
            $owners = RoleAssignment::where('scope_type', RoleAssignment::ORGANIZATION)
                ->where('scope_id', $assignment->scope_id)
                ->whereHas('role', fn ($q) => $q->where('is_owner', true))
                ->whereHas('user', fn ($q) => $q->whereNull('deactivated_at'))
                ->count();

            if ($owners <= 1) {
                throw new InvalidArgumentException('An organization needs at least one Owner. Make someone else Owner first.');
            }
        }

        $assignment->delete();
        $assignment->user?->flushAccessCache();
    }

    /** Give a person the same access lines as someone else in this organization. */
    public function copyAccess(User $from, User $to, Organization $organization): int
    {
        $count = 0;
        foreach ($from->roleAssignments()->inOrganization($organization->id)->with('role')->get() as $line) {
            if ($line->role && ! $line->role->is_owner) {
                $this->grant($to, $line->role, $line->scope_type, $line->scope_id);
                $count++;
            }
        }

        return $count;
    }

    public function deactivate(User $user): void
    {
        $user->forceFill(['deactivated_at' => now()])->save();
    }

    public function reactivate(User $user): void
    {
        $user->forceFill(['deactivated_at' => null])->save();
    }

    /** Places a role can be given in, for pickers: [['scope_type','scope_id','label','depth'], ...]. */
    public function placesIn(Organization $organization): array
    {
        $places = [[
            'scope_type' => RoleAssignment::ORGANIZATION,
            'scope_id' => $organization->id,
            'label' => 'All of '.$organization->name,
            'depth' => 0,
        ]];

        foreach ($organization->businesses()->orderBy('name')->with(['divisions' => fn ($q) => $q->orderBy('name')])->get() as $business) {
            $places[] = [
                'scope_type' => RoleAssignment::BUSINESS,
                'scope_id' => $business->id,
                'label' => $business->name.' (all divisions)',
                'depth' => 1,
            ];
            foreach ($business->divisions as $division) {
                $places[] = [
                    'scope_type' => RoleAssignment::DIVISION,
                    'scope_id' => $division->id,
                    'label' => $business->name.' › '.$division->name,
                    'depth' => 2,
                ];
            }
        }

        return $places;
    }

    /** A role can only be given inside its own organization. */
    public function organizationIdOf(string $scopeType, int $scopeId): ?int
    {
        return match ($scopeType) {
            RoleAssignment::ORGANIZATION => Organization::whereKey($scopeId)->value('id'),
            RoleAssignment::BUSINESS => Business::whereKey($scopeId)->value('organization_id'),
            RoleAssignment::DIVISION => Company::with('business')->find($scopeId)?->business?->organization_id,
            default => null,
        };
    }

    private function assertPlaceMatchesRole(Role $role, string $scopeType, int $scopeId): void
    {
        $organizationId = $this->organizationIdOf($scopeType, $scopeId);

        if (! $organizationId || $organizationId !== $role->organization_id) {
            throw new InvalidArgumentException("That role can't be given in that place.");
        }

        if ($role->is_owner && $scopeType !== RoleAssignment::ORGANIZATION) {
            throw new InvalidArgumentException('Owner can only be given for the whole organization.');
        }
    }
}
