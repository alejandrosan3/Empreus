<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany as EloquentHasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\ColumnPreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verified_at',
        'deactivated_at',
    ];

    /** Per-request cache of roles by division id. */
    protected array $rolesCache = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'is_platform_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Define the relationship: A user can have many companies.
     */
    public function companies()
    {
        // Legacy membership table, kept for history. Access now comes from role assignments.
        return $this->belongsToMany(Company::class, 'company_users', 'user_id', 'company_id');
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    public function isPlatformAdmin(): bool
    {
        return (bool) $this->is_platform_admin;
    }

    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    /**
     * Divisions (rows of the `companies` table) this person can work in.
     */
    public function accessibleDivisions(): Builder
    {
        $query = Company::query()->select('companies.*')->orderBy('companies.name');

        if ($this->isPlatformAdmin()) {
            return $query;
        }

        $assignments = $this->roleAssignments()->whereHas('role')->get(['scope_type', 'scope_id']);
        $ids = fn (string $type) => $assignments->where('scope_type', $type)->pluck('scope_id')->all();

        return $query->where(function ($q) use ($ids) {
            $q->whereIn('companies.id', $ids(RoleAssignment::DIVISION) ?: [0])
                ->orWhereIn('companies.business_id', $ids(RoleAssignment::BUSINESS) ?: [0])
                ->orWhereIn('companies.business_id', Business::whereIn('organization_id', $ids(RoleAssignment::ORGANIZATION) ?: [0])->select('id'));
        });
    }

    public function canAccessDivision(Company $division): bool
    {
        if ($this->isDeactivated()) {
            return false;
        }

        return $this->isPlatformAdmin() || $this->rolesIn($division)->isNotEmpty();
    }

    /** Roles that apply to this person in a division (the current one by default). */
    public function rolesIn(?Company $division = null): Collection
    {
        $division ??= tenancy()->division();
        if (! $division) {
            return new Collection;
        }

        return $this->rolesCache[$division->id] ??= Role::query()
            ->whereIn('id', $this->roleAssignments()->covering($division)->select('role_id'))
            ->get();
    }

    /**
     * Highest level (0 hidden … 3 full) this person has for an area in the current division.
     * While "view as role" is on, it can only go down to that role's level.
     */
    public function levelFor(string $area, ?Company $division = null): int
    {
        if ($this->isPlatformAdmin() && ! $this->viewingAsRole()) {
            return Role::levelToValue('full');
        }

        $level = $this->isPlatformAdmin()
            ? Role::levelToValue('full')
            : (int) $this->rolesIn($division)->max(fn (Role $role) => $role->levelValue($area));

        if ($viewAs = $this->viewingAsRole()) {
            $level = min($level, $viewAs->levelValue($area));
        }

        return $level;
    }

    public function hasLevel(string $area, string $level, ?Company $division = null): bool
    {
        return $this->levelFor($area, $division) >= Role::levelToValue($level);
    }

    /** Whether a sensitive detail (money, contacts, notes) is shown to this person. */
    public function sees(string $detail, ?Company $division = null): bool
    {
        $sees = $this->isPlatformAdmin()
            || $this->rolesIn($division)->contains(fn (Role $role) => $role->sees($detail));

        if ($viewAs = $this->viewingAsRole()) {
            $sees = $sees && $viewAs->sees($detail);
        }

        return $sees;
    }

    /** "all" or "own": whether this person only sees their own work in the division. */
    public function reachIn(?Company $division = null): string
    {
        if ($viewAs = $this->viewingAsRole()) {
            return $viewAs->reach;
        }

        if ($this->isPlatformAdmin()) {
            return 'all';
        }

        $roles = $this->rolesIn($division);

        return $roles->isEmpty() || $roles->contains('reach', 'all') ? 'all' : 'own';
    }

    public function isOwnerOf(Organization $organization): bool
    {
        return $this->roleAssignments()
            ->where('scope_type', RoleAssignment::ORGANIZATION)
            ->where('scope_id', $organization->id)
            ->whereHas('role', fn ($q) => $q->where('is_owner', true))
            ->exists();
    }

    /**
     * The role this person is previewing with "View as this role", if any.
     * Only people who can edit roles can preview, and only roles of the current organization.
     */
    public function viewingAsRole(): ?Role
    {
        $id = session('view_as_role_id');
        if (! $id || ! app()->bound('request') || ! request()->hasSession()) {
            return null;
        }

        if (array_key_exists('viewAs', $this->rolesCache) && $this->rolesCache['viewAs']?->id === (int) $id) {
            return $this->rolesCache['viewAs'];
        }

        $role = Role::where('organization_id', tenancy()->organizationId())->find($id);

        return $this->rolesCache['viewAs'] = $role;
    }

    /**
     * People who can be given tasks in a division (the current one by default):
     * active people whose role there is marked "can be assigned to tasks".
     */
    public function scopeWorkersInDivision(Builder $query, ?int $divisionId = null): void
    {
        $division = $divisionId ? Company::find($divisionId) : tenancy()->division();

        if (! $division) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereNull('users.deactivated_at')
            ->whereHas('roleAssignments', fn ($q) => $q->covering($division)
                ->whereHas('role', fn ($r) => $r->where('assignable', true)));
    }

    /** Forget cached roles, e.g. after access lines change. */
    public function flushAccessCache(): void
    {
        $this->rolesCache = [];
    }

    public function lotsForCurrentCompany()
    {
        $companyId = session('company_id');

        return Lot::with(['builder', 'tasks.taskType', 'tasks.status'])
            ->where('company_id', $companyId);
    }

    public function tasksForCurrentCompany()
    {
        $companyId = session('company_id');

        return Task::query()
        ->where('tasks.company_id', $companyId);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }

    /**
     * The teams this user belongs to (for workers).
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_worker', 'worker_id', 'team_id')
            ->withTimestamps();
    }


    /**
     * The tasks assigned to this user (for workers).
     */
    public function assignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_worker', 'worker_id', 'task_id')
            ->withTimestamps();
    }

    public function columnPreferences(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ColumnPreference::class);
    }

}
