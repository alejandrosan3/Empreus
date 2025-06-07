<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Task extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'lot_id',
        'company_id',
        'task_type_id',
        'price',
        'status_type_id',
        'scheduled_start_date',
        'scheduled_end_date',
        'started_at',
        'ended_at',
        'is_extra',
        'order_number',
        'original_task_name',
    ];

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function taskType(): BelongsTo
    {
        return $this->belongsTo(TaskType::class);
    }

    public function taskNotes(): HasMany
    {
        return $this->hasMany(TaskNote::class);
    }

    public function statusType(): BelongsTo
    {
        return $this->belongsTo(StatusType::class);
    }

    public function status(): BelongsTo
    {
        return $this->statusType();
    }

    public function endedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('M d, Y') : null,
        );
    }

    public function startedAt(): Attribute
    {    return Attribute::make(
            get: fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('M d, Y') : null,    );
    }
    public function scheduledStartDate(): Attribute
    {
        return Attribute::make(get: fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('M d, Y') : null,);
    }
    public function scheduledEndDate(): Attribute
    {    return Attribute::make(
            get: fn ($value) => $value ? \Carbon\Carbon::parse($value)->format('M d, Y') : null,    );
    }

    public function workers(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Specify the data to be indexed by Meilisearch.
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['lot.builder', 'taskType', 'statusType']);

        return [
            'id'          => $this->id,
            'lot_number'  => $this->lot->lot_number,
            'address'     => $this->lot->address,
            'builder'     => $this->lot->builder->name ?? null,
            'subdivision' => $this->lot->subdivision,
            'task_type'   => $this->taskType->name ?? null,
            'status'      => $this->statusType->name ?? null,
        ];
    }
}
