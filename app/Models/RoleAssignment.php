<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a person's access: "Scheduler in Cleaning › Jacksonville".
 *
 * The place (scope) is the whole organization, one business, or one division,
 * and access flows downward: a role in a business applies to all its divisions.
 */
class RoleAssignment extends Model
{
    public const ORGANIZATION = 'organization';

    public const BUSINESS = 'business';

    public const DIVISION = 'division';

    protected $fillable = ['user_id', 'role_id', 'scope_type', 'scope_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Assignments that give access to a division (directly or from above). */
    public function scopeCovering(Builder $query, Company $division): void
    {
        $division->loadMissing('business');

        $query->where(function ($q) use ($division) {
            $q->where(fn ($q) => $q->where('scope_type', self::DIVISION)->where('scope_id', $division->id))
                ->orWhere(fn ($q) => $q->where('scope_type', self::BUSINESS)->where('scope_id', $division->business_id))
                ->orWhere(fn ($q) => $q->where('scope_type', self::ORGANIZATION)->where('scope_id', $division->business?->organization_id));
        });
    }

    /** Assignments anywhere inside an organization. */
    public function scopeInOrganization(Builder $query, int $organizationId): void
    {
        $businessIds = Business::where('organization_id', $organizationId)->pluck('id');
        $divisionIds = Company::whereIn('business_id', $businessIds)->pluck('id');

        $query->where(function ($q) use ($organizationId, $businessIds, $divisionIds) {
            $q->where(fn ($q) => $q->where('scope_type', self::ORGANIZATION)->where('scope_id', $organizationId))
                ->orWhere(fn ($q) => $q->where('scope_type', self::BUSINESS)->whereIn('scope_id', $businessIds))
                ->orWhere(fn ($q) => $q->where('scope_type', self::DIVISION)->whereIn('scope_id', $divisionIds));
        });
    }

    public function scopeModel(): Organization|Business|Company|null
    {
        return match ($this->scope_type) {
            self::ORGANIZATION => Organization::find($this->scope_id),
            self::BUSINESS => Business::withTrashed()->find($this->scope_id),
            self::DIVISION => Company::withTrashed()->with('business')->find($this->scope_id),
            default => null,
        };
    }

    /** "All of Empreus", "Cleaning (all divisions)", "Cleaning › Jacksonville" */
    public function placeLabel(): string
    {
        $place = $this->scopeModel();

        return match (true) {
            $place instanceof Organization => 'All of '.$place->name,
            $place instanceof Business => $place->name.' (all divisions)',
            $place instanceof Company => $place->fullName(),
            default => 'Unknown place',
        };
    }
}
