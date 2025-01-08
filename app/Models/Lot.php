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

    public function hasTaskType($taskTypeName): bool
    {
        return $this->tasks->contains(function ($task) use ($taskTypeName) {
            return $task->type === $taskTypeName;
        });
    }

    public function getTaskByTypeName($taskTypeName)
    {
        // Use the first() method to find the first task with the specified type name
        return $this->tasks->first(function ($task) use ($taskTypeName) {
            return $task->taskType->name === $taskTypeName;
        }) ?: false;
    }

}
