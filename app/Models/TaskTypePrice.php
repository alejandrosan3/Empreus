<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A builder's own price for a task type. */
class TaskTypePrice extends Model
{
    protected $fillable = ['task_type_id', 'builder_id', 'price'];

    protected $casts = ['price' => 'decimal:2'];

    public function taskType(): BelongsTo
    {
        return $this->belongsTo(TaskType::class);
    }

    public function builder(): BelongsTo
    {
        return $this->belongsTo(Builder::class);
    }
}
