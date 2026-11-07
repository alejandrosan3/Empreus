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
    private function assertPlaceMatchesRole(Role $role, string $scopeType, int $scopeId): void
    {
        $organizationId = match ($scopeType) {
            RoleAssignment::ORGANIZATION => Organization::whereKey($scopeId)->value('id'),
            RoleAssignment::BUSINESS => Business::whereKey($scopeId)->value('organization_id'),
            RoleAssignment::DIVISION => Company::with('business')->find($scopeId)?->business?->organization_id,
            default => null,
        };

        if (! $organizationId || $organizationId !== $role->organization_id) {
            throw new InvalidArgumentException("That role can't be given in that place.");
        }

        if ($role->is_owner && $scopeType !== RoleAssignment::ORGANIZATION) {
            throw new InvalidArgumentException('Owner can only be given for the whole organization.');
        }
    }
}
