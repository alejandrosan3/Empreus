<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lot extends Model
{
    use HasFactory;

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
}
