<?php

namespace App\Models;

use Flux\DateRange;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use App\Traits\Duplicatable;

class Lot extends Model
{
    use HasFactory, Searchable, Duplicatable;

    /**
     * @var array
     */
    protected $fillable = [
        'company_id',
        'builder_id',
        'supervisor_id',
        'lot_number',
        'phase',
        'status_type_id',
        'address',
        'subdivision',
        'floor_area',
        'num_of_floors',
    ];
    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
    ];

    public function builder(): BelongsTo
    {
        return $this->belongsTo(Builder::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Supervisor::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function statusType(): BelongsTo
    {
        return $this->belongsTo(StatusType::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function status(): BelongsTo
    {
        return $this->statusType();
    }

    /**
     * @param $taskTypeName
     * @return bool
     */
    public function hasTaskType($taskTypeName): bool
    {
        return $this->tasks->contains(function ($task) use ($taskTypeName) {
            return $task->type === $taskTypeName;
        });
    }

    /**
     * @param $taskTypeName
     * @return false|Task
     */
    public function getTaskByTypeName($taskTypeName)
    {
        // Use the first() method to find the first task with the specified type name
        return $this->tasks->first(function ($task) use ($taskTypeName) {
            return $task->taskType->name === $taskTypeName;
        }) ?: false;
    }

    /**
     * Get the last completed task for this lot.
     *
     * @return Task|null
     */
    public function getLastCompletedTask()
    {
        // "last" means the largest ended_at value
        return $this->tasks()
            ->whereNotNull('ended_at')       // only completed tasks
            ->orderByDesc('ended_at')        // sort by ended_at descending
            ->first();                       // return the first (i.e. newest)
    }

    public function getSupervisorNameAttribute()
    {
        $name = $this->lot->supervisor->name ?? '';
        return \Str::title($name);
    }

    /**
     * Retrieve a distinct list of all subdivisions from the database that are not null, ordered alphabetically.
     *
     * @return array An array of unique subdivisions.
     */
    public static function getAllSubdivisions(): array
    {
        return self::query()
            ->select('subdivision')
            ->distinct()
            ->whereNotNull('subdivision')
            ->orderBy('subdivision')
            ->pluck('subdivision')
            ->toArray();
    }

    /**
     * Data for Meilisearch indexing.
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['builder', 'supervisor']);

        return [
            'id'         => $this->id,
            'lot_number' => $this->lot_number,
            'address'    => $this->address,
            'subdivision'=> $this->subdivision,
            'builder'    => $this->builder->name ?? null,
            'supervisor' => $this->supervisor->name ?? null,
        ];
    }

    public function scopeConfirmedTasksInRange(
        \Illuminate\Database\Eloquent\Builder $query,
        DateRange $range
    ): \Illuminate\Database\Eloquent\Builder {
        return $query->whereHas('tasks', function ($q) use ($range) {
            $q->whereBetween('scheduled_start_date', [$range->start(), $range->end()])
                ->whereHas('statusType', fn ($qq) =>
                $qq->whereIn('identifier', ['confirmed', 'in_progress'])
                );
        });
    }

    /**
     * Determine the house type for this lot using configuration.
     * Always returns a non-empty string; falls back to the configured default.
     */
    public function determineHouseType(): string
    {
        $types           = (array) config('house.types', []);
        $abbrMap         = (array) config('house.abbreviations', []);
        $fallbackFloors  = (array) config('house.fallback_by_floors', []);

        // Resolve default display label (from config), with a safe final fallback
        $defaultKey   = $fallbackFloors['default'] ?? null;
        $defaultLabel = ($defaultKey && isset($types[$defaultKey]))
            ? $types[$defaultKey]
            : (reset($types) ?: 'Single Family');

        $subdivision = (string) ($this->subdivision ?? '');

        // Rule 1: Check subdivision abbreviations (case-insensitive, whole word)
        if ($subdivision !== '' && !empty($abbrMap)) {
            foreach ($abbrMap as $abbr => $typeKey) {
                $pattern = '/\b' . preg_quote((string) $abbr, '/') . '\b/i';
                if (preg_match($pattern, $subdivision)) {
                    return $types[$typeKey] ?? $defaultLabel;
                }
            }
        }

        // Rule 2: Fallback to num_of_floors
        if ($this->num_of_floors !== null) {
            $floors  = (int) $this->num_of_floors;
            $typeKey = $fallbackFloors[(string) $floors] ?? null;

            if (!$typeKey) {
                if ($floors > 1 && isset($fallbackFloors['more_than_one'])) {
                    $typeKey = $fallbackFloors['more_than_one'];
                } elseif ($defaultKey) {
                    $typeKey = $defaultKey;
                }
            }

            return $typeKey ? ($types[$typeKey] ?? $defaultLabel) : $defaultLabel;
        }

        // No subdivision hit and floors unknown: return default
        return $defaultLabel;
    }

    /**
     * Accessor: $lot->house_type
     *
     * @return string|null
     */
    public function getHouseTypeAttribute(): ?string
    {
        return $this->determineHouseType();
    }

    /**
     * Get the number of stories for this lot.
     * Defaults to 1 if the number of floors is not explicitly set.
     */
    public function getStoriesCountAttribute(): int
    {
        return (int) ($this->num_of_floors ?? 1);
    }



}
