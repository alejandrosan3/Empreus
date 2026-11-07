<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A named set of permissions: one level per area (hidden, view, edit, full),
 * a few sensitive-details switches and a reach (all records or only their own).
 *
 * Roles with no organization form the platform library that any organization
 * can copy from.
 */
class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id', 'key', 'name', 'description', 'levels', 'details', 'reach', 'assignable', 'is_owner',
    ];

    protected $casts = [
        'levels' => 'array',
        'details' => 'array',
        'assignable' => 'boolean',
        'is_owner' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    public function scopeLibrary($query): void
    {
        $query->whereNull('organization_id');
    }

    public function isLibraryTemplate(): bool
    {
        return $this->organization_id === null;
    }

    /** Numeric level (0 hidden … 3 full) for an area. */
    public function levelValue(string $area): int
    {
        if ($this->is_owner) {
            return self::levelToValue('full');
        }

        return self::levelToValue($this->levels[$area] ?? 'hidden');
    }

    public function level(string $area): string
    {
        return self::valueToLevel($this->levelValue($area));
    }

    public function sees(string $detail): bool
    {
        return $this->is_owner || (bool) ($this->details[$detail] ?? false);
    }

    /**
     * Plain-language summary, e.g. "Overseers can see the scheduler and tasks.
     * They can change nothing. They can't see lots or settings."
     */
    public function summary(): string
    {
        if ($this->is_owner) {
            return "{$this->name} can do everything, including managing people, roles and settings.";
        }

        $areas = config('permissions.areas');
        $byLevel = ['view' => [], 'edit' => [], 'full' => [], 'hidden' => []];
        foreach ($areas as $key => $area) {
            $byLevel[$this->level($key)][] = mb_strtolower($area['label']);
        }

        $sentences = [];
        $canChange = array_merge($byLevel['edit'], $byLevel['full']);
        $canSee = array_merge($byLevel['view'], $canChange);

        $sentences[] = $canSee
            ? "Can see ".self::listing($canSee).'.'
            : "Can't open anything yet.";

        if ($canChange) {
            $sentences[] = 'Can change '.self::listing($canChange).'.';
        } elseif ($canSee) {
            $sentences[] = "Can't change anything.";
        }

        if ($byLevel['full']) {
            $sentences[] = 'Can archive and restore in '.self::listing($byLevel['full']).'.';
        }

        $hiddenDetails = collect(config('permissions.details'))
            ->reject(fn ($d, $key) => $this->sees($key))
            ->map(fn ($d) => mb_strtolower($d['label']))
            ->values()->all();
        if ($canSee && $hiddenDetails) {
            $sentences[] = "Doesn't see ".self::listing($hiddenDetails).'.';
        }

        if ($this->reach === 'own') {
            $sentences[] = 'Only sees their own work: tasks assigned to them or their team, and lots they supervise.';
        }

        return implode(' ', $sentences);
    }

    public static function levelToValue(string $level): int
    {
        return config("permissions.levels.$level.value", 0);
    }

    public static function valueToLevel(int $value): string
    {
        foreach (config('permissions.levels') as $key => $level) {
            if ($level['value'] === $value) {
                return $key;
            }
        }

        return 'hidden';
    }

    /** Turns the "*full" shortcut in config into one level per area. */
    public static function expandLevels(array|string $levels): array
    {
        $areas = array_keys(config('permissions.areas'));

        if (is_string($levels)) {
            return array_fill_keys($areas, ltrim($levels, '*'));
        }

        return array_merge(array_fill_keys($areas, 'hidden'), array_intersect_key($levels, array_flip($areas)));
    }

    /** Copy this role into an organization (used for starter roles and the library). */
    public function copyTo(Organization $organization, ?string $name = null): self
    {
        $name ??= $this->name;
        $base = $name;
        $n = 2;
        while (self::withTrashed()->where('organization_id', $organization->id)->where('name', $name)->exists()) {
            $name = "$base ($n)";
            $n++;
        }

        return self::create([
            'organization_id' => $organization->id,
            'key' => $this->key,
            'name' => $name,
            'description' => $this->description,
            'levels' => self::expandLevels($this->levels ?? []),
            'details' => $this->details,
            'reach' => $this->reach,
            'assignable' => $this->assignable,
            'is_owner' => false,
        ]);
    }

    /** Create the starter roles in a new organization. */
    public static function createStarterRoles(Organization $organization): void
    {
        foreach (config('permissions.starter_roles') as $key => $role) {
            self::firstOrCreate(
                ['organization_id' => $organization->id, 'key' => $key],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'levels' => self::expandLevels($role['levels']),
                    'details' => $role['details'],
                    'reach' => $role['reach'],
                    'assignable' => $role['assignable'],
                    'is_owner' => $role['is_owner'] ?? false,
                ]
            );
        }
    }

    private static function listing(array $items): string
    {
        if (count($items) <= 1) {
            return $items[0] ?? '';
        }

        $last = array_pop($items);

        return implode(', ', $items).' and '.$last;
    }
}
